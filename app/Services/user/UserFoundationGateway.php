<?php

declare(strict_types=1);

namespace App\Services\user;

use App\Exceptions\FoundationAuthRequiredException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use JsonException;
use Paganini\Aggregation\Support\DownstreamPayload;
use RuntimeException;

readonly class UserFoundationGateway
{
    public function __construct(
        private ResolvedFoundationBaseUrl $resolvedFoundationBaseUrl,
    ) {}

    /**
     * @throws FoundationAuthRequiredException
     * @throws ConnectionException
     * @throws BindingResolutionException
     * @throws JsonException
     */
    public function fetchCurrentUser(Request $request): array
    {
        $baseUrl = $this->resolvedFoundationBaseUrl->resolve();
        if ($baseUrl === '') {
            throw new RuntimeException('Missing user foundation base_url configuration.');
        }

        $timeout = (int) config('user_agg.foundation.timeout_seconds', 3);
        $endpoint = (string) config('user_agg.foundation.me_endpoint', '/api/user/me');
        $token = (string) $request->bearerToken();

        $response = Http::timeout($timeout)
            ->withToken($token)
            ->acceptJson()
            ->get($baseUrl.$endpoint);

        if (! $response->successful()) {
            if ($response->status() === 401) {
                throw $this->authRequiredFromHttpResponse($response);
            }
            throw new RuntimeException('Failed to fetch base user info from foundation service.');
        }

        return DownstreamPayload::extractData($response->json(), 'foundation user service');
    }

    private function authRequiredFromHttpResponse(ClientResponse $response): FoundationAuthRequiredException
    {
        $json = $response->json();
        $detail = 'login required';
        if (is_array($json) && isset($json['message']) && is_string($json['message']) && $json['message'] !== '') {
            $detail = $json['message'];
        }

        return new FoundationAuthRequiredException(
            'Downstream error from foundation user service: '.$detail
        );
    }
}
