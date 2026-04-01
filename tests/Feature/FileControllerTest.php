<?php

namespace Tests\Feature;

use App\Jobs\TranscodeVideoJob;
use App\Models\MediaFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uploads_file_and_queues_transcoding_job(): void
    {
        Storage::fake('s3');
        Queue::fake();

        $response = $this->postJson('/api/files', [
            'file' => UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', MediaFile::STATUS_UPLOADED);

        $record = MediaFile::query()->first();
        $this->assertNotNull($record);
        $this->assertSame(MediaFile::MIME_VIDEO_MP4, $record->mime_type);
        $this->assertIsInt($record->ct);
        $this->assertIsInt($record->ut);
        $this->assertGreaterThan(0, $record->ct);
        $this->assertGreaterThan(0, $record->ut);
        Storage::disk('s3')->assertExists($record->raw_path);

        Queue::assertPushed(TranscodeVideoJob::class, function (TranscodeVideoJob $job) use ($record) {
            return $job->mediaFileId === $record->id;
        });
    }

    public function test_it_accepts_image_uploads(): void
    {
        Storage::fake('s3');
        Queue::fake();

        $response = $this->postJson('/api/files', [
            'file' => UploadedFile::fake()->create('image.png', 1024, 'image/png'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', MediaFile::STATUS_UPLOADED);

        $record = MediaFile::query()->first();
        $this->assertNotNull($record);
        $this->assertSame(MediaFile::MIME_IMAGE_PNG, $record->mime_type);
        Storage::disk('s3')->assertExists($record->raw_path);
        Queue::assertPushed(TranscodeVideoJob::class);
    }

    public function test_it_falls_back_to_extension_when_client_mime_is_octet_stream(): void
    {
        Storage::fake('s3');
        Queue::fake();

        $response = $this->postJson('/api/files', [
            'file' => UploadedFile::fake()->create('sample.mp4', 1024, 'application/octet-stream'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', MediaFile::STATUS_UPLOADED);

        $record = MediaFile::query()->first();
        $this->assertNotNull($record);
        $this->assertSame(MediaFile::MIME_VIDEO_MP4, $record->mime_type);
        Queue::assertPushed(TranscodeVideoJob::class);
    }

    public function test_it_returns_processing_status_when_file_not_ready(): void
    {
        $file = MediaFile::query()->create([
            'original_name' => 'clip.mp4',
            'mime_type' => MediaFile::MIME_VIDEO_MP4,
            'size_bytes' => 12345,
            'raw_path' => 'media/raw/clip.mp4',
            'status' => MediaFile::STATUS_TRANSCODING,
        ]);

        $response = $this->getJson('/api/files/'.$file->id.'/download');

        $response->assertStatus(202)
            ->assertJsonPath('code', 1);
    }
}

