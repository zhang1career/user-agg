<?php

namespace App\Services\user\business_services;

class AccountProfileService extends HttpBusinessServiceAdapter
{
    public function key(): string
    {
        return 'account_profile';
    }

    protected function configKey(): string
    {
        return 'account_profile';
    }

    protected function endpoint(array $subject, array $context): string
    {
        return (string) config('user_agg.downstream.account_profile.endpoint', '/api/user/profile');
    }
}
