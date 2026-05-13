<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\user\UserAggregationExecutor;
use App\Services\user\UserAggregationService;
use App\Services\user\UserDegradePolicy;
use App\Services\user\UserFoundationGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Paganini\Capability\ProviderRegistry;
use Random\RandomException;

class UserAggregationController extends Controller
{
    /**
     * @throws RandomException
     */
    public function me(
        Request $request,
        UserAggregationService $aggregation,
        UserFoundationGateway $foundationGateway,
        ProviderRegistry $registry,
        UserAggregationExecutor $executor,
        UserDegradePolicy $degradePolicy
    ): JsonResponse {
        return $aggregation->me(
            $request,
            $foundationGateway,
            $registry,
            $executor,
            $degradePolicy
        );
    }
}
