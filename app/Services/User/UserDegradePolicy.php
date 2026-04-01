<?php

namespace App\Services\User;

use Paganini\UserAggregation\Policies\DefaultDegradePolicy;

class UserDegradePolicy extends DefaultDegradePolicy
{
    public function __construct()
    {
        parent::__construct(
            (string) config('user_agg.degrade.strategy', self::STRATEGY_MASK_NULL),
            (string) config('user_agg.degrade.mask_error_message', 'Service temporarily unavailable.')
        );
    }
}
