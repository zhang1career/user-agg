<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\open_api\OpenApiService;
use Illuminate\Http\JsonResponse;
use JsonException;

/**
 * Serves the canonical OpenAPI document at {@code docs/api.json} for public
 * {@code /api/*} tooling and contract checks.
 */
class OpenApiController extends Controller
{
    public function __construct(
        private readonly OpenApiService $openApi,
    ) {}

    /**
     * @throws JsonException
     */
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->openApi->loadCanonicalSpec(), 200, [
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
