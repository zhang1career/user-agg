<?php

return [
    'cdn_enabled' => (bool) env('OSS_CDN_ENABLED', true),
    'raw_prefix' => env('MEDIA_RAW_PREFIX', 'media/raw'),
    'transcoded_prefix' => env('MEDIA_TRANSCODED_PREFIX', 'media/transcoded'),
    'ffmpeg_binary' => env('FFMPEG_BINARY', 'ffmpeg'),
    'ffmpeg_timeout' => (int) env('FFMPEG_TIMEOUT', 600),
    'ffmpeg_preset' => env('FFMPEG_PRESET', 'veryfast'),
    'ffmpeg_crf' => (int) env('FFMPEG_CRF', 23),

    /*
    |--------------------------------------------------------------------------
    | Transcoding Rules (adaptive by file type)
    |--------------------------------------------------------------------------
    |
    | transcodable_mimes: MIME codes that require ffmpeg transcoding to mp4.
    |   Default: 1,2,3 = video/mp4, video/quicktime, video/webm
    |   MIME codes: 1=mp4, 2=mov, 3=webm, 4=mp3, 5=jpeg, 6=png, 7=pdf, 8=txt, 9=octet-stream, 0=unknown
    |
    | default_action: For mimes not in transcodable_mimes.
    |   - passthrough: Use raw file as output (no conversion), CDN serves original
    |   - fail: Mark as FAILED (useful if you only want transcodable types)
    |
    */
    'transcodable_mimes' => array_map('intval', array_filter(
        explode(',', (string) env('MEDIA_TRANSCODABLE_MIMES', '1,2,3'))
    )),
    'default_action' => env('MEDIA_DEFAULT_ACTION', 'passthrough'),
];

