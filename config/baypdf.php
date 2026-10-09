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
    'pdf_compression' => true,
    'limits' => [
        'max_collections' => 10,
        'max_collection_fields' => 20,
        'max_collection_rows' => 500,
        'max_collection_string_bytes' => 5000,
        'max_collection_payload_kb' => 1024,
        'max_generated_pages' => 100,
        'max_layout_elements' => 200,
    ],
    'font_cache' => storage_path('app/private/baypdf/fonts'),
    // Register document types and variable definitions in a host service provider.
];
