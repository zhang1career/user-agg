<?php

namespace App\Jobs;

use App\Models\MediaFile;
use App\Services\MediaTranscoder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class TranscodeVideoJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $mediaFileId,
    ) {
    }

    public function handle(MediaTranscoder $transcoder): void
    {
        $mediaFile = MediaFile::query()->find($this->mediaFileId);
        if (!$mediaFile) {
            return;
        }

        $mediaFile->update([
            'status' => MediaFile::STATUS_TRANSCODING,
            'error_message' => null,
        ]);

        try {
            $transcoder->process($mediaFile);
        } catch (Throwable $exception) {
            $mediaFile->update([
                'status' => MediaFile::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}

