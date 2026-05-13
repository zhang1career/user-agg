<?php

declare(strict_types=1);

namespace App\Services\user;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use JsonException;
use Paganini\Memo\CacheKeyGenerator;
use Paganini\Memo\Memoizer;
use Paganini\ServiceDiscovery\Contracts\ServiceUriResolverInterface;
use Paganini\ServiceDiscovery\ServiceUrlSpecifier;

/**
 * Resolves `user_agg.foundation.base_url` (from API_GATEWAY_BASE_URL): Fusio-compatible `://{{service_key}}`
 * via Redis when present; otherwise returns trimmed URL unchanged.
 *
 * Memoizes resolved URLs to avoid Redis on every request (TTL from config).
 * {@see ServiceUriResolverInterface} is resolved only when the template contains `://{{` (lazy), so plain URLs never open Redis.
 */
final readonly class ResolvedFoundationBaseUrl
{
    public function __construct(
        private Application $app,
        private Memoizer    $memoizer,
        private int         $memoTtlSeconds,
    ) {}

    /**
     * Trimmed base URL, or empty string if unset.
     * @throws JsonException|BindingResolutionException
     */
    public function resolve(): string
    {
        $raw = (string) config('user_agg.foundation.base_url', '');
        if ($raw === '') {
            return '';
        }
        if (! str_contains($raw, '://{{')) {
            return rtrim($raw, '/');
        }

        $cacheKey = 'user_agg:foundation_base:'.CacheKeyGenerator::fromAssociativeArray(['u' => $raw]);

        return rtrim(
            $this->memoizer->getOrCompute(
                $cacheKey,
                $this->memoTtlSeconds,
                fn (): string => ServiceUrlSpecifier::specifyHost(
                    $raw,
                    $this->app->make(ServiceUriResolverInterface::class)
                )
            ),
            '/'
        );
    }
}
