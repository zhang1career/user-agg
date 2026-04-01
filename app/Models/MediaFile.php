<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaFile extends Model
{
    public const STATUS_INIT = 0;
    public const STATUS_UPLOADED = 1;
    public const STATUS_TRANSCODING = 2;
    public const STATUS_READY = 3;
    public const STATUS_FAILED = 4;

    public const MIME_UNKNOWN = 0;
    public const MIME_VIDEO_MP4 = 1;
    public const MIME_VIDEO_QUICKTIME = 2;
    public const MIME_VIDEO_WEBM = 3;
    public const MIME_AUDIO_MPEG = 4;
    public const MIME_IMAGE_JPEG = 5;
    public const MIME_IMAGE_PNG = 6;
    public const MIME_APPLICATION_PDF = 7;
    public const MIME_TEXT_PLAIN = 8;
    public const MIME_APPLICATION_OCTET_STREAM = 9;

    public $incrementing = true;
    public $timestamps = false;
    protected $keyType = 'int';

    protected $fillable = [
        'original_name',
        'mime_type',
        'size_bytes',
        'raw_path',
        'transcoded_path',
        'cdn_url',
        'status',
        'error_message',
        'ct',
        'ut',
    ];

    protected $casts = [
        'id' => 'integer',
        'size_bytes' => 'integer',
        'mime_type' => 'integer',
        'status' => 'integer',
        'ct' => 'integer',
        'ut' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (MediaFile $mediaFile) {
            $now = self::nowMilliseconds();
            $mediaFile->ct = $mediaFile->ct ?? $now;
            $mediaFile->ut = $mediaFile->ut ?? $now;
        });

        static::updating(function (MediaFile $mediaFile) {
            $mediaFile->ut = self::nowMilliseconds();
        });
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function isVideo(): bool
    {
        return in_array($this->mime_type, [
            self::MIME_VIDEO_MP4,
            self::MIME_VIDEO_QUICKTIME,
            self::MIME_VIDEO_WEBM,
        ], true);
    }

    public static function mimeTypeFromString(?string $mimeType): int
    {
        return match (strtolower((string) $mimeType)) {
            'video/mp4' => self::MIME_VIDEO_MP4,
            'video/quicktime' => self::MIME_VIDEO_QUICKTIME,
            'video/webm' => self::MIME_VIDEO_WEBM,
            'audio/mpeg' => self::MIME_AUDIO_MPEG,
            'image/jpeg', 'image/jpg' => self::MIME_IMAGE_JPEG,
            'image/png' => self::MIME_IMAGE_PNG,
            'application/pdf' => self::MIME_APPLICATION_PDF,
            'text/plain' => self::MIME_TEXT_PLAIN,
            'application/octet-stream' => self::MIME_APPLICATION_OCTET_STREAM,
            default => self::MIME_UNKNOWN,
        };
    }

    public static function mimeTypeFromExtension(?string $extension): int
    {
        return match (strtolower((string) $extension)) {
            'mp4' => self::MIME_VIDEO_MP4,
            'mov' => self::MIME_VIDEO_QUICKTIME,
            'webm' => self::MIME_VIDEO_WEBM,
            'mp3' => self::MIME_AUDIO_MPEG,
            'jpg', 'jpeg' => self::MIME_IMAGE_JPEG,
            'png' => self::MIME_IMAGE_PNG,
            'pdf' => self::MIME_APPLICATION_PDF,
            'txt' => self::MIME_TEXT_PLAIN,
            default => self::MIME_UNKNOWN,
        };
    }

    public static function mimeTypeName(int $mimeType): string
    {
        return match ($mimeType) {
            self::MIME_VIDEO_MP4 => 'video/mp4',
            self::MIME_VIDEO_QUICKTIME => 'video/quicktime',
            self::MIME_VIDEO_WEBM => 'video/webm',
            self::MIME_AUDIO_MPEG => 'audio/mpeg',
            self::MIME_IMAGE_JPEG => 'image/jpeg',
            self::MIME_IMAGE_PNG => 'image/png',
            self::MIME_APPLICATION_PDF => 'application/pdf',
            self::MIME_TEXT_PLAIN => 'text/plain',
            self::MIME_APPLICATION_OCTET_STREAM => 'application/octet-stream',
            default => 'unknown',
        };
    }

    private static function nowMilliseconds(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}

