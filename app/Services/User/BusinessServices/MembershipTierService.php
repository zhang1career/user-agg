<?php

namespace App\Services\User\BusinessServices;

class MembershipTierService extends HttpBusinessServiceAdapter
{
    public function serviceKey(): string
    {
        return 'membership_tier';
    }

    protected function configKey(): string
    {
        return 'membership_tier';
    }

    protected function endpoint(array $baseUser, array $context): string
    {
        return (string) config('user_agg.downstream.membership_tier.endpoint', '/api/user/membership');
    }
}
