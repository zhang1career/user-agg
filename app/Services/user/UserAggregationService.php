<?php

declare(strict_types=1);

namespace App\Services\user;

use App\Components\ApiResponse;
use App\Exceptions\FoundationAuthRequiredException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JsonException;
use Paganini\Capability\ProviderRegistry;
use Random\RandomException;

final class UserAggregationService
{
    /**
     * @throws BindingResolutionException
     * @throws ConnectionException
     * @throws FoundationAuthRequiredException
     * @throws JsonException
     * @throws RandomException
     */
    public function me(
        Request $request,
        UserFoundationGateway $foundationGateway,
        ProviderRegistry $registry,
        UserAggregationExecutor $executor,
        UserDegradePolicy $degradePolicy,
    ): JsonResponse {
        $reqId = $request->header('X-Request-Id') ?: bin2hex(random_bytes(8));

        $token = $request->bearerToken();
        if ($token === null || trim($token) === '') {
            return response()->json(ApiResponse::error(
                (int) config('user_agg.foundation.unauthorized_code', 40101),
                'Authorization required. Call POST /api/user/login first, store access_token on the client, then call this endpoint with header: Authorization: Bearer <access_token> (single space after Bearer).',
                $reqId
            ), 401);
        }

        $baseUser = $foundationGateway->fetchCurrentUser($request);

        Log::info('[api] handled request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'route_uri' => $request->route()?->uri(),
            'ip' => $request->ip(),
            'x_forwarded_for' => $request->header('X-Forwarded-For'),
            'x_trace_id' => $request->header('X-Trace-Id'),
            'user_agent' => $request->userAgent(),
            'query' => $request->query(),
            'handler' => 'me',
            'foundation_user_id' => $baseUser['id'] ?? $baseUser['user_id'] ?? null,
        ]);

        $context = [
            'path' => $request->path(),
            'query' => $request->query(),
            'headers' => $request->headers->all(),
            'trace_id' => $request->header('X-Trace-Id'),
            'bearer_token' => $request->bearerToken(),
        ];

        $result = $executor->execute(
            $registry->matched($context),
            $baseUser,
            $context,
            $degradePolicy
        );

        $hasDegraded = $result->hasDegraded();
        $responseCode = $hasDegraded
            ? (int) config('user_agg.degrade.partial_failure_code', 20601)
            : 0;
        $responseMsg = $hasDegraded
            ? (string) config('user_agg.degrade.partial_failure_message', 'Partially failed, degraded by aggregator.')
            : '';

        return response()->json(ApiResponse::code([
            'user' => $baseUser,
            'biz' => $result->biz,
            'meta' => [
                'degraded' => $hasDegraded,
                'degraded_keys' => $result->degradedKeys,
                'keys_used' => $result->keysUsed,
                'degrade_strategy' => $degradePolicy->strategy(),
                'execution_mode' => (string) config('user_agg.execution.mode', 'serial'),
            ],
        ], $responseCode, $responseMsg, $reqId));
    }
}
