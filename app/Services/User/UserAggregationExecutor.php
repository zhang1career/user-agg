<?php

namespace App\Services\User;

use Illuminate\Support\Facades\Concurrency;
use Paganini\UserAggregation\Execution\AggregationExecutor as BaseAggregationExecutor;
use Paganini\UserAggregation\DTO\AggregationResult;
use Paganini\UserAggregation\Contracts\BusinessServiceContract;

class UserAggregationExecutor
{
    /**
     * @param array<BusinessServiceContract> $services
     */
    public function execute(array $services, array $baseUser, array $context, UserDegradePolicy $degradePolicy): AggregationResult
    {
        $mode = (string) config('user_agg.execution.mode', 'serial');
        $executor = new BaseAggregationExecutor();
        return $executor->execute(
            $services,
            $baseUser,
            $context,
            $degradePolicy,
            $mode,
            fn (array $tasks): array => Concurrency::run($tasks)
        );
    }
}
