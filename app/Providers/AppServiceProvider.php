<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Client\OutboundRequestIdMiddleware;
use App\Infrastructure\service_discovery\LaravelRedisStringClient;
use App\Logging\monolog\TodayAppLogHandler;
use App\Logging\processors\XRequestIdLogProcessor;
use App\Queue\connectors\DatabaseMillisConnector;
use App\Queue\failed\DatabaseUuidFailedJobProviderMillis;
use App\Services\user\ResolvedFoundationBaseUrl;
use DateTimeZone;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Paganini\Capability\ProviderRegistry;
use Paganini\Memo\ApcuMemoStore;
use Paganini\Memo\ArrayMemoStore;
use Paganini\Memo\Memoizer;
use Paganini\ServiceDiscovery\Contracts\ServiceUriResolverInterface;
use Paganini\ServiceDiscovery\RedisServiceUriResolver;
use function function_exists;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LaravelRedisStringClient::class, function (Application $app) {
            $conn = (string) config('user_agg.foundation.service_discovery.redis_connection', 'default');

            return new LaravelRedisStringClient($app['redis']->connection($conn));
        });

        $this->app->singleton(ServiceUriResolverInterface::class, function (Application $app) {
            return new RedisServiceUriResolver(
                $app->make(LaravelRedisStringClient::class),
                (string) config('user_agg.foundation.service_discovery.redis_key_prefix', '')
            );
        });

        $this->app->singleton(ResolvedFoundationBaseUrl::class, function (Application $app) {
            $ttl = (int) config('user_agg.foundation.service_discovery.memo_ttl_seconds', 60);
            if ($ttl < 0) {
                $ttl = 0;
            }
            $store = function_exists('apcu_fetch') ? new ApcuMemoStore('user_agg.foundation_base') : new ArrayMemoStore;

            return new ResolvedFoundationBaseUrl(
                $app,
                new Memoizer($store),
                $ttl
            );
        });

        $this->app->singleton(ProviderRegistry::class, function ($app) {
            $serviceDefs = (array) config('user_agg.business_services', []);
            $serviceClasses = [];
            foreach ($serviceDefs as $def) {
                if (is_string($def)) {
                    $serviceClasses[] = $def;

                    continue;
                }
                if (is_array($def) && ($def['enabled'] ?? true) === true && is_string($def['class'] ?? null)) {
                    $serviceClasses[] = $def['class'];
                }
            }

            $services = array_map(fn (string $class) => $app->make($class), $serviceClasses);

            return new ProviderRegistry($services);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Log::extend('app_today', function ($app, array $config) {
            $handler = new TodayAppLogHandler(
                $config['path'],
                (int) ($config['days'] ?? 0),
                $this->level($config),
                $config['bubble'] ?? true,
                $config['permission'] ?? null,
                $config['locking'] ?? false
            );

            $processors = [];
            if ($config['replace_placeholders'] ?? false) {
                $processors[] = new PsrLogMessageProcessor;
            }
            $processors[] = new XRequestIdLogProcessor;

            $tz = new DateTimeZone((string) config('app.timezone'));

            return new Logger(
                $this->parseChannel($config),
                [$this->prepareHandler($handler, $config)],
                $processors,
                $tz
            );
        });

        Http::globalRequestMiddleware([OutboundRequestIdMiddleware::class, 'addHeader']);

        // Use custom database queue with ct and millisecond timestamps
        $this->app['queue']->addConnector('database', function () {
            return new DatabaseMillisConnector($this->app['db']);
        });

        // Use custom failed job provider with failed_at in milliseconds
        $this->app->extend('queue.failer', function ($failer, $app) {
            $config = $app['config']['queue.failed'];
            if (isset($config['driver']) && $config['driver'] === 'database-uuids') {
                return new DatabaseUuidFailedJobProviderMillis(
                    $app['db'],
                    $config['database'] ?? $app['config']['database.default'],
                    $config['table']
                );
            }

            return $failer;
        });
    }
}
