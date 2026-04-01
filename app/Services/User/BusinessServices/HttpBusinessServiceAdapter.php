<?php

namespace App\Services\User\BusinessServices;

use App\Contracts\UserBusinessServiceContract;
use Illuminate\Support\Facades\Http;
use Paganini\UserAggregation\Exceptions\DownstreamServiceException;
use Paganini\UserAggregation\Support\DownstreamPayload;

abstract class HttpBusinessServiceAdapter implements UserBusinessServiceContract
{
    public function supports(array $context): bool
    {
        return true;
    }

    final public function fetch(array $baseUser, array $context): array
    {
        $response = Http::timeout($this->timeoutSeconds())
            ->acceptJson()
            ->withHeaders($this->headers($baseUser, $context))
            ->get($this->baseUrl() . $this->endpoint($baseUser, $context), $this->query($baseUser, $context));

        if (!$response->successful()) {
            throw new DownstreamServiceException(
                sprintf('Downstream service %s failed with status %d.', $this->serviceKey(), $response->status())
            );
        }

        $data = DownstreamPayload::extractData($response->json(), $this->serviceKey());

        return $this->mapData($data, $baseUser, $context);
    }

    abstract protected function configKey(): string;

    protected function endpoint(array $baseUser, array $context): string
    {
        return '/api/user/profile';
    }

    protected function query(array $baseUser, array $context): array
    {
        return [];
    }

    protected function headers(array $baseUser, array $context): array
    {
        $headers = [];
        if (!empty($context['bearer_token'])) {
            $headers['Authorization'] = 'Bearer ' . $context['bearer_token'];
        }
        if (!empty($context['trace_id'])) {
            $headers['X-Trace-Id'] = $context['trace_id'];
        }
        return $headers;
    }

    protected function timeoutSeconds(): int
    {
        return (int) config("user_agg.downstream.{$this->configKey()}.timeout_seconds", 3);
    }

    protected function baseUrl(): string
    {
        $baseUrl = rtrim((string) config("user_agg.downstream.{$this->configKey()}.base_url", ''), '/');
        if ($baseUrl === '') {
            throw new DownstreamServiceException(
                sprintf('Missing base_url for business service %s.', $this->serviceKey())
            );
        }
        return $baseUrl;
    }

    protected function mapData(array $data, array $baseUser, array $context): array
    {
        return $data;
    }
}
