<?php

return [
    // Disabled until the host explicitly enables the designer and defines its Gate.
    'enabled' => false,
    'path' => 'baypdf',
    'middleware' => ['web', 'auth'],
    'gate' => 'manage-baypdf',
    'scoping' => [
        // When enabled, the host must bind BayPdf\Contracts\ScopeResolver.
        'enabled' => false,
    ],
    'locale' => 'en',
    'disk' => 'local',
    'asset_prefix' => 'baypdf/assets',
    'max_upload_kb' => 5120,
    'max_image_pixels' => 16000000,
    'font_cache' => storage_path('app/private/baypdf/fonts'),
    // Register document types and variable definitions in a host service provider.
];
