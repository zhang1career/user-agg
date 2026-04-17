<?php

namespace App\Services\User\BusinessServices;

use App\Contracts\UserBusinessServiceContract;
use Illuminate\Support\Facades\Http;
use Paganini\Aggregation\Exceptions\DownstreamServiceException;
use Paganini\Aggregation\Support\DownstreamPayload;

abstract class HttpBusinessServiceAdapter implements UserBusinessServiceContract
{
    public function supports(array $context): bool
    {
        return true;
    }

    final public function fetch(array $subject, array $context): array
    {
        $response = Http::timeout($this->timeoutSeconds())
            ->acceptJson()
            ->withHeaders($this->headers($subject, $context))
            ->get($this->baseUrl() . $this->endpoint($subject, $context), $this->query($subject, $context));

        if (!$response->successful()) {
            throw new DownstreamServiceException(
                sprintf('Downstream service %s failed with status %d.', $this->key(), $response->status())
            );
        }

        $data = DownstreamPayload::extractData($response->json(), $this->key());

        return $this->mapData($data, $subject, $context);
    }

    abstract protected function configKey(): string;

    protected function endpoint(array $subject, array $context): string
    {
        return '/api/user/profile';
    }

    protected function query(array $subject, array $context): array
    {
        return [];
    }

    protected function headers(array $subject, array $context): array
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
                sprintf('Missing base_url for business service %s.', $this->key())
            );
        }

        return $baseUrl;
    }

    protected function mapData(array $data, array $subject, array $context): array
    {
        return $data;
    }
}
