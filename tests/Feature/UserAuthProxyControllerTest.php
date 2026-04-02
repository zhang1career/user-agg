<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserAuthProxyControllerTest extends TestCase
{
    public function test_login_forwards_body_and_returns_downstream_payload(): void
    {
        config()->set('user_agg.foundation.base_url', 'http://foundation.local');
        config()->set('user_agg.foundation.login_endpoint', '/api/user/login');
        config()->set('user_agg.foundation.timeout_seconds', 3);

        Http::fake([
            'http://foundation.local/api/user/login' => Http::response(
                '{"errorCode":0,"data":{"access_token":"tok-xyz"},"message":""}',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $response = $this->postJson('/api/user/login', [
            'username' => 'u',
            'password' => 'p',
        ]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('data.access_token', 'tok-xyz');

        Http::assertSent(function ($request) {
            return $request->url() === 'http://foundation.local/api/user/login'
                && $request->method() === 'POST'
                && str_contains($request->body(), '"username":"u"');
        });
    }

    public function test_put_login_forwards_refresh_to_foundation_login_path(): void
    {
        config()->set('user_agg.foundation.base_url', 'http://foundation.local');
        config()->set('user_agg.foundation.refresh_endpoint', '/api/user/login');

        Http::fake([
            'http://foundation.local/api/user/login' => Http::response(
                '{"errorCode":0,"data":{"access_token":"tok-new"},"message":""}',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $response = $this->putJson('/api/user/login', [
            'refresh_token' => 'rt-abc',
        ]);

        $response->assertOk()->assertJsonPath('data.access_token', 'tok-new');

        Http::assertSent(function ($request) {
            return $request->url() === 'http://foundation.local/api/user/login'
                && $request->method() === 'PUT'
                && str_contains($request->body(), '"refresh_token":"rt-abc"');
        });
    }
}
