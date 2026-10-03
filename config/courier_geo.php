<?php

return [
    'base_url' => env('COURIER_GEO_BASE_URL', 'https://admin.4nortes.app'),
    'webdriver_url' => env('COURIER_GEO_WEBDRIVER_URL', 'http://127.0.0.1:9515'),
    'chromium_binary' => env('COURIER_GEO_CHROMIUM_BINARY', '/usr/bin/chromium'),
    'export_timeout' => 1800,
    'download_timeout' => 600,
    'max_file_bytes' => 100 * 1024 * 1024,
];
