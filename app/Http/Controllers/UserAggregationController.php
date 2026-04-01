<?php

namespace App\Http\Controllers;

use App\Components\ApiResponse;
use App\Services\User\UserAggregationExecutor;
use App\Services\User\UserDegradePolicy;
use App\Services\User\UserFoundationGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Paganini\UserAggregation\Registry\BusinessServiceRegistry;

class UserAggregationController extends Controller
{
    public function me(
        Request $request,
        UserFoundationGateway $foundationGateway,
        BusinessServiceRegistry $registry,
        UserAggregationExecutor $executor,
        UserDegradePolicy $degradePolicy
    ): JsonResponse {
        $baseUser = $foundationGateway->fetchCurrentUser($request);

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
                'degraded_services' => $result->degradedServices,
                'services_used' => $result->servicesUsed,
                'degrade_strategy' => $degradePolicy->strategy(),
                'execution_mode' => (string) config('user_agg.execution.mode', 'serial'),
            ],
        ], $responseCode, $responseMsg));
    }
}
