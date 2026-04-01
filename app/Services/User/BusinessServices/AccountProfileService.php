<?php

namespace App\Services\User\BusinessServices;

class AccountProfileService extends HttpBusinessServiceAdapter
{
    public function serviceKey(): string
    {
        return 'account_profile';
    }

    protected function configKey(): string
    {
        return 'account_profile';
    }

    protected function endpoint(array $baseUser, array $context): string
    {
        return (string) config('user_agg.downstream.account_profile.endpoint', '/api/user/profile');
    }
}
