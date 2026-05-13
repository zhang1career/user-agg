<?php

namespace Tests\Feature;

use App\Services\user\business_services\AccountProfileService;
use App\Services\user\business_services\MembershipTierService;
use Illuminate\Support\Facades\Http;
use Paganini\Capability\ProviderRegistry;
use Tests\TestCase;

class UserAggregationControllerTest extends TestCase
{
    public function test_me_returns_401_when_authorization_header_missing(): void
    {
        $response = $this->getJson('/api/user/me');

        $response->assertStatus(401)
            ->assertJsonPath('errorCode', 40101);
        $this->assertStringContainsString('Authorization required', (string) $response->json('message'));
    }

    public function test_me_returns_aggregated_user_and_business_data(): void
    {
        config()->set('user_agg.foundation.base_url', 'http://foundation.local');
        config()->set('user_agg.foundation.me_endpoint', '/api/user/me');
        config()->set('user_agg.business_services', [
            ['class' => AccountProfileService::class, 'enabled' => true],
            ['class' => MembershipTierService::class, 'enabled' => true],
        ]);
        config()->set('user_agg.downstream.account_profile.base_url', 'http://biz.local');
        config()->set('user_agg.downstream.account_profile.endpoint', '/api/user/profile');
        config()->set('user_agg.downstream.membership_tier.base_url', 'http://biz.local');
        config()->set('user_agg.downstream.membership_tier.endpoint', '/api/user/membership');
        config()->set('user_agg.execution.mode', 'serial');
        config()->set('user_agg.degrade.strategy', 'mask_null');

        $this->app->forgetInstance(ProviderRegistry::class);

        Http::fake([
            'http://foundation.local/api/user/me' => Http::response([
                'errorCode' => 0,
                'data' => ['id' => 101, 'username' => 'mini'],
                'message' => '',
            ], 200),
            'http://biz.local/api/user/profile' => Http::response([
                'errorCode' => 0,
                'data' => ['nickname' => 'mini-dev'],
                'message' => '',
            ], 200),
            'http://biz.local/api/user/membership' => Http::response([
                'errorCode' => 0,
                'data' => ['tier' => 'gold'],
                'message' => '',
            ], 200),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer token-abc',
            'X-Trace-Id' => 'trace-001',
        ])->getJson('/api/user/me');

        $response->assertOk()
            ->assertJsonPath('errorCode', 0)
            ->assertJsonPath('data.user.id', 101)
            ->assertJsonPath('data.biz.account_profile.nickname', 'mini-dev')
            ->assertJsonPath('data.biz.membership_tier.tier', 'gold')
            ->assertJsonPath('data.meta.degraded', false);
    }

    public function test_me_returns_partial_failure_code_when_a_service_fails(): void
    {
        config()->set('user_agg.foundation.base_url', 'http://foundation.local');
        config()->set('user_agg.foundation.me_endpoint', '/api/user/me');
        config()->set('user_agg.business_services', [
            ['class' => AccountProfileService::class, 'enabled' => true],
            ['class' => MembershipTierService::class, 'enabled' => true],
        ]);
        config()->set('user_agg.downstream.account_profile.base_url', 'http://biz.local');
        config()->set('user_agg.downstream.account_profile.endpoint', '/api/user/profile');
        config()->set('user_agg.downstream.membership_tier.base_url', 'http://biz.local');
        config()->set('user_agg.downstream.membership_tier.endpoint', '/api/user/membership');
        config()->set('user_agg.execution.mode', 'serial');
        config()->set('user_agg.degrade.strategy', 'mask_error_object');
        config()->set('user_agg.degrade.partial_failure_code', 20601);

        $this->app->forgetInstance(ProviderRegistry::class);

        Http::fake([
            'http://foundation.local/api/user/me' => Http::response([
                'errorCode' => 0,
                'data' => ['id' => 101],
                'message' => '',
            ], 200),
            'http://biz.local/api/user/profile' => Http::response([
                'errorCode' => 0,
                'data' => ['nickname' => 'mini-dev'],
                'message' => '',
            ], 200),
            'http://biz.local/api/user/membership' => Http::response([
                'errorCode' => 13001,
                'data' => [],
                'message' => 'service down',
            ], 200),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer token-abc',
        ])->getJson('/api/user/me');

        $response->assertOk()
            ->assertJsonPath('errorCode', 20601)
            ->assertJsonPath('data.meta.degraded', true)
            ->assertJsonPath('data.meta.degraded_keys.0', 'membership_tier')
            ->assertJsonPath('data.biz.account_profile.nickname', 'mini-dev')
            ->assertJsonPath('data.biz.membership_tier.degraded', true);
    }
}
