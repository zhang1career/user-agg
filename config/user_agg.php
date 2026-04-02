<?php

use App\Services\User\BusinessServices\AccountProfileService;
use App\Services\User\BusinessServices\MembershipTierService;

return [
    'foundation' => [
        'base_url' => env('USER_CENTER_BASE_URL', ''),
        'me_endpoint' => env('USER_CENTER_ME_ENDPOINT', '/api/user/me'),
        'login_endpoint' => env('USER_CENTER_LOGIN_ENDPOINT', '/api/user/login'),
        'refresh_endpoint' => env('USER_CENTER_REFRESH_ENDPOINT', '/api/user/login'),
        'timeout_seconds' => (int) env('USER_CENTER_TIMEOUT_SECONDS', 3),
        'unauthorized_code' => (int) env('USER_CENTER_UNAUTHORIZED_CODE', 40101),
    ],

    'business_services' => [
        [
            'class' => AccountProfileService::class,
            'enabled' => (bool) env('USER_BIZ_ACCOUNT_PROFILE_ENABLED', true),
        ],
        [
            'class' => MembershipTierService::class,
            'enabled' => (bool) env('USER_BIZ_MEMBERSHIP_TIER_ENABLED', false),
        ],
    ],

    'downstream' => [
        'account_profile' => [
            'base_url' => env('USER_BIZ_ACCOUNT_PROFILE_BASE_URL', ''),
            'endpoint' => env('USER_BIZ_ACCOUNT_PROFILE_ENDPOINT', '/api/user/profile'),
            'timeout_seconds' => (int) env('USER_BIZ_ACCOUNT_PROFILE_TIMEOUT_SECONDS', 3),
        ],
        'membership_tier' => [
            'base_url' => env('USER_BIZ_MEMBERSHIP_TIER_BASE_URL', ''),
            'endpoint' => env('USER_BIZ_MEMBERSHIP_TIER_ENDPOINT', '/api/user/membership'),
            'timeout_seconds' => (int) env('USER_BIZ_MEMBERSHIP_TIER_TIMEOUT_SECONDS', 3),
        ],
    ],

    'execution' => [
        'mode' => env('USER_AGG_EXECUTION_MODE', 'serial'),
    ],

    'degrade' => [
        'strategy' => env('USER_AGG_DEGRADE_STRATEGY', 'mask_null'),
        'mask_error_message' => env('USER_AGG_DEGRADE_MASK_ERROR_MESSAGE', 'Service temporarily unavailable.'),
        'partial_failure_code' => (int) env('USER_AGG_PARTIAL_FAILURE_CODE', 20601),
        'partial_failure_message' => env('USER_AGG_PARTIAL_FAILURE_MESSAGE', 'Partially failed, degraded by aggregator.'),
    ],
];
