<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserAuthProxyControllerTest extends TestCase
{
    public function test_register_forwards_to_foundation_register_endpoint(): void
    {
        config()->set('user_agg.foundation.base_url', 'http://foundation.local');
        config()->set('user_agg.foundation.register_endpoint', '/api/user/register');

        Http::fake([
            'http://foundation.local/api/user/register' => Http::response(
                '{"errorCode":0,"data":{"event_id":1},"message":""}',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $response = $this->postJson('/api/user/register', [
            'username' => 'u1',
            'password' => 'p1',
            'notice_channel' => 'email',
            'notice_target' => 'u1@example.com',
        ]);

        $response->assertOk()->assertJsonPath('data.event_id', 1);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://foundation.local/api/user/register'
                && $request->method() === 'POST'
                && str_contains($request->body(), '"username":"u1"');
        });
    }

    public function test_register_verify_forwards_to_foundation_register_verify_endpoint(): void
    {
        config()->set('user_agg.foundation.base_url', 'http://foundation.local');
        config()->set('user_agg.foundation.register_verify_endpoint', '/api/user/register/verify');

        Http::fake([
            'http://foundation.local/api/user/register/verify' => Http::response(
                '{"errorCode":0,"data":{"access_token":"tok-reg","refresh_token":"ref-reg"},"message":""}',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $response = $this->postJson('/api/user/register/verify', [
            'event_id' => 1,
            'code' => '123456',
        ]);

        $response->assertOk()->assertJsonPath('data.access_token', 'tok-reg');

        Http::assertSent(function ($request) {
            return $request->url() === 'http://foundation.local/api/user/register/verify'
                && $request->method() === 'POST'
                && str_contains($request->body(), '"event_id":1');
        });
    }

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

    public function test_reset_password_request_forwards_to_foundation_endpoint(): void
    {
        config()->set('user_agg.foundation.base_url', 'http://foundation.local');
        config()->set('user_agg.foundation.reset_password_request_endpoint', '/api/user/reset-password');

        Http::fake([
            'http://foundation.local/api/user/reset-password' => Http::response(
                '{"errorCode":0,"data":{"sent":true,"event_id":7},"message":""}',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $response = $this->postJson('/api/user/reset-password', [
            'channel' => 'email',
            'target' => 'u1@example.com',
        ]);

        $response->assertOk()->assertJsonPath('data.event_id', 7);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://foundation.local/api/user/reset-password'
                && $request->method() === 'POST'
                && str_contains($request->body(), '"channel":"email"');
        });
    }

    public function test_reset_password_verify_forwards_to_foundation_endpoint(): void
    {
        config()->set('user_agg.foundation.base_url', 'http://foundation.local');
        config()->set('user_agg.foundation.reset_password_verify_endpoint', '/api/user/reset-password/verify');

        Http::fake([
            'http://foundation.local/api/user/reset-password/verify' => Http::response(
                '{"errorCode":0,"data":{"reset":true},"message":""}',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $response = $this->postJson('/api/user/reset-password/verify', [
            'event_id' => 7,
            'code' => '888888',
            'new_password' => 'new-pass',
        ]);

        $response->assertOk()->assertJsonPath('data.reset', true);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://foundation.local/api/user/reset-password/verify'
                && $request->method() === 'POST'
                && str_contains($request->body(), '"new_password":"new-pass"');
        });
    }
}
