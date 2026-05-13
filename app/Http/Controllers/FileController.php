<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\media\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function __construct(
        private readonly FileService $files,
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $this->logHandledApiRequest($request, ['handler' => __FUNCTION__]);

        return $this->files->upload($request);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->logHandledApiRequest($request, ['handler' => __FUNCTION__, 'id' => $id]);

        return $this->files->show($request, $id);
    }

    public function download(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $this->logHandledApiRequest($request, ['handler' => __FUNCTION__, 'id' => $id]);

        return $this->files->download($request, $id);
    }
}
