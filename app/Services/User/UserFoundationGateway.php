<?php

namespace App\Services\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Paganini\UserAggregation\Support\DownstreamPayload;
use RuntimeException;

class UserFoundationGateway
{
    public function fetchCurrentUser(Request $request): array
    {
        $baseUrl = rtrim((string) config('user_agg.foundation.base_url'), '/');
        if ($baseUrl === '') {
            throw new RuntimeException('Missing user foundation base_url configuration.');
        }

        $timeout = (int) config('user_agg.foundation.timeout_seconds', 3);
        $endpoint = (string) config('user_agg.foundation.me_endpoint', '/api/user/me');
        $token = (string) $request->bearerToken();

        $response = Http::timeout($timeout)
            ->withToken($token)
            ->acceptJson()
            ->get($baseUrl . $endpoint);

        if (!$response->successful()) {
            throw new RuntimeException('Failed to fetch base user info from foundation service.');
        }

        return DownstreamPayload::extractData($response->json(), 'foundation user service');
    }
}
