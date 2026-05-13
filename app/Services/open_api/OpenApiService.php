<?php

declare(strict_types=1);

namespace App\Services\open_api;

use JsonException;
use RuntimeException;

final class OpenApiService
{
    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function loadCanonicalSpec(): array
    {
        $path = base_path('docs/api.json');
        if (! is_file($path)) {
            throw new RuntimeException('OpenAPI document not found at docs/api.json.');
        }

        $raw = (string) file_get_contents($path);
        if ($raw === '') {
            throw new RuntimeException('OpenAPI document is empty.');
        }

        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAPI document is not a JSON object.');
        }

        return $decoded;
    }
}
