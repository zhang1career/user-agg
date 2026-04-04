<?php

namespace App\Services\User;

use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class UserFoundationAuthProxy
{
    public function forwardRegister(Request $request): Response
    {
        return $this->forwardPost(
            $request,
            (string)config('user_agg.foundation.register_endpoint', '/api/user/register')
        );
    }

    public function forwardRegisterVerify(Request $request): Response
    {
        return $this->forwardPost(
            $request,
            (string)config('user_agg.foundation.register_verify_endpoint', '/api/user/register/verify')
        );
    }

    public function forwardLogin(Request $request): Response
    {
        return $this->forwardPost(
            $request,
            (string)config('user_agg.foundation.login_endpoint', '/api/user/login')
        );
    }

    public function forwardRefresh(Request $request): Response
    {
        return $this->forwardPut(
            $request,
            (string)config('user_agg.foundation.refresh_endpoint', '/api/user/login')
        );
    }

    public function forwardResetPasswordRequest(Request $request): Response
    {
        return $this->forwardPost(
            $request,
            (string)config('user_agg.foundation.reset_password_request_endpoint', '/api/user/reset-password')
        );
    }

    public function forwardResetPasswordVerify(Request $request): Response
    {
        return $this->forwardPost(
            $request,
            (string)config('user_agg.foundation.reset_password_verify_endpoint', '/api/user/reset-password/verify')
        );
    }

    private function forwardPost(Request $request, string $endpoint): Response
    {
        list($url, $pending, $content) = $this->extractArgs($endpoint, $request);
        if ($content !== '') {
            $response = $pending
                ->withBody($content, $request->header('Content-Type') ?: 'application/json')
                ->post($url);
        } else {
            $response = $pending->asJson()->post($url, $request->all());
        }

        return $this->toRawResponse($response);
    }

    private function forwardPut(Request $request, string $endpoint): Response
    {
        list($url, $pending, $content) = $this->extractArgs($endpoint, $request);
        if ($content !== '') {
            $response = $pending
                ->withBody($content, $request->header('Content-Type') ?: 'application/json')
                ->put($url);
        } else {
            $response = $pending->asJson()->put($url, $request->all());
        }

        return $this->toRawResponse($response);
    }

    /**
     * @param string $endpoint
     * @param Request $request
     * @return array
     */
    private function extractArgs(string $endpoint, Request $request): array
    {
        $baseUrl = rtrim((string)config('user_agg.foundation.base_url'), '/');
        if ($baseUrl === '') {
            throw new RuntimeException('Missing user foundation base_url configuration.');
        }

        $timeout = (int)config('user_agg.foundation.timeout_seconds', 3);
        $url = $baseUrl . $endpoint;

        $pending = Http::timeout($timeout)->withHeaders($this->forwardHeaders($request));

        $content = $request->getContent();
        return array($url, $pending, $content);
    }

    /**
     * @return array<string, string>
     */
    private function forwardHeaders(Request $request): array
    {
        $headers = [];
        foreach (['Authorization', 'Accept', 'Content-Type', 'X-Trace-Id', 'X-Request-Id'] as $name) {
            $line = $request->headers->get($name);
            if ($line !== null && $line !== '') {
                $headers[$name] = $line;
            }
        }

        return $headers;
    }

    private function toRawResponse(ClientResponse $response): Response
    {
        $out = response($response->body(), $response->status());
        $ct = $response->header('Content-Type');
        if ($ct !== null && $ct !== '') {
            $out->header('Content-Type', $ct);
        }

        return $out;
    }
}
