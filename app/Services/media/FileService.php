<?php

declare(strict_types=1);

namespace App\Services\media;

use App\Components\ApiResponse;
use App\Jobs\TranscodeVideoJob;
use App\Models\MediaFile;
use App\Repos\MediaFileRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class FileService
{
    public function __construct(
        private MediaFileRepo $mediaFiles,
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:102400'],
        ]);

        $uploadedFile = $validated['file'];
        $extension = $uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: 'bin';
        $mimeType = $uploadedFile->getClientMimeType() ?: 'application/octet-stream';
        $mimeTypeCode = MediaFile::mimeTypeFromString($mimeType);
        if (in_array($mimeTypeCode, [MediaFile::MIME_UNKNOWN, MediaFile::MIME_APPLICATION_OCTET_STREAM], true)) {
            $mimeTypeCode = MediaFile::mimeTypeFromExtension($extension);
        }

        $pathId = (string) Str::uuid();
        $rawPath = trim(config('media.raw_prefix', 'raw'), '/').'/'.$pathId.'.'.$extension;

        $uploaded = Storage::disk('s3')->putFileAs(
            dirname($rawPath),
            $uploadedFile,
            basename($rawPath)
        );
        if (! $uploaded) {
            throw new RuntimeException('Failed to upload file to object storage.');
        }

        $mediaFile = $this->mediaFiles->create([
            'original_name' => $uploadedFile->getClientOriginalName(),
            'mime_type' => $mimeTypeCode,
            'size_bytes' => $uploadedFile->getSize(),
            'raw_path' => $rawPath,
            'status' => MediaFile::STATUS_UPLOADED,
        ]);

        TranscodeVideoJob::dispatch($mediaFile->id);

        return response()->json(ApiResponse::ok([
            'id' => $mediaFile->id,
            'status' => $mediaFile->status,
            'cdn_url' => $mediaFile->cdn_url,
            'ready' => false,
        ]), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $mediaFile = $this->mediaFiles->findOrFail($id);

        return response()->json(ApiResponse::ok([
            'id' => $mediaFile->id,
            'original_name' => $mediaFile->original_name,
            'mime_type' => $mediaFile->mime_type,
            'mime_type_name' => MediaFile::mimeTypeName($mediaFile->mime_type),
            'size_bytes' => $mediaFile->size_bytes,
            'status' => $mediaFile->status,
            'cdn_url' => $mediaFile->cdn_url,
            'error_message' => $mediaFile->error_message,
            'ready' => $mediaFile->isReady(),
            'ct' => $mediaFile->ct,
            'ut' => $mediaFile->ut,
        ]));
    }

    public function download(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $mediaFile = $this->mediaFiles->findOrFail($id);
        if (! $mediaFile->isReady() || ! $mediaFile->cdn_url) {
            return response()->json(ApiResponse::error(1, 'File is still processing.'), 202);
        }

        return redirect()->away($mediaFile->cdn_url);
    }
}
