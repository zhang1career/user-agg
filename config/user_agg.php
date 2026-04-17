<?php

use App\Services\User\BusinessServices\AccountProfileService;
use App\Services\User\BusinessServices\MembershipTierService;

return [
    /*
    | api.log_http_errors: 记录所有 api/* 且 HTTP 状态为 4xx、5xx 的响应（含转发下游的「非异常」错误）。
    | api.normalize_5xx_json_body: 为 true 时，将 5xx 的 JSON 响应体替换为统一 errorCode/message（默认关闭，避免掩盖下游语义）。
    */
    'api' => [
        'log_http_errors' => (bool) env('USER_AGG_API_LOG_HTTP_ERRORS', true),
        'normalize_5xx_json_body' => (bool) env('USER_AGG_API_NORMALIZE_5XX_JSON', false),
        'normalize_5xx_message' => env('USER_AGG_API_NORMALIZE_5XX_MESSAGE', '服务器内部错误'),
    ],

    'foundation' => [
        'base_url' => env('API_GATEWAY_BASE_URL', ''),
        /*
         * When `base_url` contains `://{{service_key}}` (Fusio-style), resolve via Redis (paganini).
         * Plain URLs skip Redis entirely.
         */
        'service_discovery' => [
            'memo_ttl_seconds' => (int) env('API_GATEWAY_SD_MEMO_TTL', 60),
            'redis_connection' => env('API_GATEWAY_SD_DB_CONN', 'default'),
            'redis_key_prefix' => env('API_GATEWAY_SD_KEY_PREFIX', ''),
        ],
        'me_endpoint' => env('USER_CENTER_ME_ENDPOINT', '/api/user/me'),
        'login_endpoint' => env('USER_CENTER_LOGIN_ENDPOINT', '/api/user/login'),
        'refresh_endpoint' => env('USER_CENTER_REFRESH_ENDPOINT', '/api/user/login'),
        'register_endpoint' => env('USER_CENTER_REGISTER_ENDPOINT', '/api/user/register'),
        'register_verify_endpoint' => env('USER_CENTER_REGISTER_VERIFY_ENDPOINT', '/api/user/register/verify'),
        'reset_password_request_endpoint' => env('USER_CENTER_RESET_PASSWORD_ENDPOINT', '/api/user/reset-password'),
        'reset_password_verify_endpoint' => env('USER_CENTER_RESET_PASSWORD_VERIFY_ENDPOINT', '/api/user/reset-password/verify'),
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
