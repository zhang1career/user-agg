<?php

use App\Models\MediaFile;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('media:config', function () {
    $transcodable = config('media.transcodable_mimes', []);
    $defaultAction = config('media.default_action', 'passthrough');

    $mimeNames = [
        0 => 'unknown', 1 => 'video/mp4', 2 => 'video/quicktime', 3 => 'video/webm',
        4 => 'audio/mpeg', 5 => 'image/jpeg', 6 => 'image/png', 7 => 'application/pdf',
        8 => 'text/plain', 9 => 'application/octet-stream',
    ];

    $this->info('Transcoding config:');
    $this->line('  transcodable_mimes: ' . json_encode($transcodable));
    $this->line('  default_action: ' . $defaultAction);
    $this->newLine();
    $this->info('MIME codes that will be transcoded (ffmpeg -> mp4):');
    foreach ($transcodable as $code) {
        $this->line('  ' . $code . ' = ' . ($mimeNames[$code] ?? 'unknown'));
    }
    $this->newLine();
    $this->info('Other types (e.g. 6=png): ' . $defaultAction);
    $this->line('  → PNG (6) transcoded? ' . (in_array(6, $transcodable, true) ? 'YES' : 'NO'));
})->purpose('Show current media transcoding config (for debugging .env)');
