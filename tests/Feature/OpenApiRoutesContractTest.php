<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Keeps {@see base_path('docs/api.json')} aligned with registered HTTP routes.
 */
final class OpenApiRoutesContractTest extends TestCase
{
    public function test_spec_file_is_valid_openapi_document(): void
    {
        $path = base_path('docs/api.json');
        $this->assertFileExists($path);
        /** @var array<string, mixed> $spec */
        $spec = json_decode(file_get_contents($path) ?: 'null', true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('3.0.3', $spec['openapi']);
        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('paths', $spec);
        $this->assertIsArray($spec['paths']);
        $this->assertArrayHasKey('components', $spec);
        /** @var array<string, mixed> $components */
        $components = $spec['components'];
        $this->assertArrayHasKey('schemas', $components);
    }

    public function test_openapi_endpoint_serves_spec_file_verbatim(): void
    {
        $response = $this->getJson('/api/openapi.json');
        $response->assertOk()->assertHeader('content-type', 'application/json');
        $body = $response->json();
        $this->assertSame('3.0.3', $body['openapi']);
        $this->assertArrayHasKey('/api/openapi.json', $body['paths']);
    }

    public function test_openai_json_alias_serves_same_document(): void
    {
        $openApi = $this->getJson('/api/openapi.json')->assertOk()->json();
        $openAi = $this->getJson('/api/openai.json')->assertOk()->json();
        $this->assertSame($openApi, $openAi);
    }

    public function test_documented_paths_only_cover_public_api_surface(): void
    {
        /** @var array<string, mixed> $spec */
        $spec = json_decode(file_get_contents(base_path('docs/api.json')) ?: 'null', true, 512, JSON_THROW_ON_ERROR);
        foreach (array_keys($spec['paths']) as $openApiPath) {
            $path = (string) $openApiPath;
            $isPublic = str_starts_with($path, '/api/') || $path === '/up';
            $this->assertTrue(
                $isPublic,
                'docs/api.json must only document public surface (/api/* or /up); got '.$path,
            );
            $this->assertStringStartsNotWith('/internal/', $path, 'Internal routes must not appear in docs/api.json.');
        }
    }

    public function test_documented_paths_match_laravel_routes(): void
    {
        /** @var array<string, mixed> $spec */
        $spec = json_decode(file_get_contents(base_path('docs/api.json')) ?: 'null', true, 512, JSON_THROW_ON_ERROR);
        $map = $this->methodUriRegistrationMap();

        foreach ($spec['paths'] as $openApiPath => $operations) {
            $this->assertIsString($openApiPath);
            $uri = ltrim($openApiPath, '/');
            $this->assertNotSame('', $uri, 'OpenAPI path must not be empty.');

            $this->assertIsArray($operations);
            foreach ($operations as $methodLower => $_) {
                if (! is_string($methodLower)) {
                    continue;
                }
                if (! in_array(strtolower($methodLower), ['get', 'post', 'patch', 'put', 'delete', 'options'], true)) {
                    continue;
                }
                $method = strtoupper($methodLower);
                $this->assertArrayHasKey(
                    "{$method} {$uri}",
                    $map,
                    "OpenAPI documents {$method} {$openApiPath} but Laravel has no matching route."
                );
            }
        }
    }

    /**
     * @return array<string, true>
     */
    private function methodUriRegistrationMap(): array
    {
        $map = [];
        foreach (Route::getRoutes() as $route) {
            foreach ($route->methods() as $method) {
                if ($method === 'HEAD') {
                    continue;
                }
                $map[$method.' '.$route->uri()] = true;
            }
        }

        return $map;
    }
}
