<?php

namespace App\Providers;

use App\Queue\Connectors\DatabaseMillisConnector;
use App\Queue\Failed\DatabaseUuidFailedJobProviderMillis;
use Illuminate\Support\ServiceProvider;
use Paganini\UserAggregation\Registry\BusinessServiceRegistry;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BusinessServiceRegistry::class, function ($app) {
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
            return new BusinessServiceRegistry($services);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
