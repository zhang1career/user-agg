<?php

namespace App\Services;

use App\Models\MediaFile;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MediaTranscoder
{
    public function process(MediaFile $mediaFile): void
    {
        $transcodableMimes = config('media.transcodable_mimes', [1, 2, 3]);
        $isTranscodable = in_array($mediaFile->mime_type, $transcodableMimes, true);

        if (!$isTranscodable) {
            $this->passthrough($mediaFile);

            return;
        }

        $rawStream = Storage::disk('s3')->readStream($mediaFile->raw_path);
        if ($rawStream === false) {
            throw new RuntimeException('Unable to read source file from S3.');
        }

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            throw new RuntimeException('Unable to create temp directory.');
        }

        $sourceTmpPath = $tmpDir.'/'.$mediaFile->id.'-src';
        $targetTmpPath = $tmpDir.'/'.$mediaFile->id.'-transcoded.mp4';

        $targetHandle = fopen($sourceTmpPath, 'wb');
        if ($targetHandle === false) {
            throw new RuntimeException('Unable to create temp source file.');
        }

        stream_copy_to_stream($rawStream, $targetHandle);
        fclose($rawStream);
        fclose($targetHandle);

        $targetS3Path = trim(config('media.transcoded_prefix', 'transcoded'), '/').'/'.$mediaFile->id.'.mp4';

        try {
            $this->transcodeWithFfmpeg($sourceTmpPath, $targetTmpPath);
            $uploadStream = fopen($targetTmpPath, 'rb');
            if ($uploadStream === false) {
                throw new RuntimeException('Unable to read transcoded file.');
            }
            Storage::disk('s3')->put($targetS3Path, $uploadStream);
            fclose($uploadStream);

            $mediaFile->update([
                'status' => MediaFile::STATUS_READY,
                'transcoded_path' => $targetS3Path,
                'cdn_url' => $this->buildCdnUrl($targetS3Path),
                'error_message' => null,
            ]);
        } finally {
            if (is_file($sourceTmpPath)) {
                unlink($sourceTmpPath);
            }
            if (is_file($targetTmpPath)) {
                unlink($targetTmpPath);
            }
        }
    }

    private function transcodeWithFfmpeg(string $sourcePath, string $targetPath): void
    {
        $command = [
            config('media.ffmpeg_binary', 'ffmpeg'),
            '-y',
            '-i',
            $sourcePath,
            '-c:v',
            'libx264',
            '-preset',
            config('media.ffmpeg_preset', 'veryfast'),
            '-crf',
            (string) config('media.ffmpeg_crf', 23),
            '-c:a',
            'aac',
            '-b:a',
            '128k',
            $targetPath,
        ];

        $result = Process::timeout((int) config('media.ffmpeg_timeout', 600))->run($command);
        if ($result->failed()) {
            throw new ProcessFailedException($result);
        }
    }

    private function buildCdnUrl(string $path): string
    {
        if (config('media.cdn_enabled', true)) {
            $domain = trim((string) config('services.cloudfront.domain'), '/');
            if ($domain === '') {
                throw new RuntimeException('CDN is enabled but CloudFront domain is not configured.');
            }

            if (!str_starts_with($domain, 'http://') && !str_starts_with($domain, 'https://')) {
                $domain = 'https://'.$domain;
            }

            return $domain.'/'.ltrim($path, '/');
        }

        return Storage::disk('s3')->url($path);
    }

    /**
     * Passthrough: use raw file as output without conversion.
     * For images, PDFs, audio, etc. that don't need transcoding.
     */
    private function passthrough(MediaFile $mediaFile): void
    {
        $defaultAction = config('media.default_action', 'passthrough');

        if ($defaultAction === 'fail') {
            throw new RuntimeException(
                'File type is not in transcodable_mimes. Set MEDIA_DEFAULT_ACTION=passthrough to allow.'
            );
        }

        $mediaFile->update([
            'status' => MediaFile::STATUS_READY,
            'transcoded_path' => $mediaFile->raw_path,
            'cdn_url' => $this->buildCdnUrl($mediaFile->raw_path),
            'error_message' => null,
        ]);
    }
}

