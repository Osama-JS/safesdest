<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | Default Dompdf configuration for Laravel project with Arabic support
    |
    */

    'show_warnings' => false,   // Throw an Exception on warnings from dompdf
    'public_path' => null,      // Override the public path if needed
    'convert_entities' => true, // Convert HTML entities

    'options' => [

        // Fonts
        'font_dir' => storage_path('fonts/'), // directory for storing fonts
        'font_cache' => storage_path('fonts/'), // font metrics cache
        'default_font' => 'tajawal',           // default font

        'fonts' => [
            'tajawal' => [
                'R' => storage_path('fonts/Tajawal-Regular.ttf'),
                'B' => storage_path('fonts/Tajawal-Bold.ttf'),
                'I' => storage_path('fonts/Tajawal-Regular.ttf'),
                'useOTL' => 0xFF,    // Enable complex script support (Arabic shaping)
                'useKashida' => 75,  // Improve character connection
            ],
        ],

        // Directories
        'temp_dir' => sys_get_temp_dir(),
        'chroot' => realpath(base_path()),

        // Allowed protocols
        'allowed_protocols' => [
            'data://' => ['rules' => []],
            'file://' => ['rules' => []],
            'http://' => ['rules' => []],
            'https://' => ['rules' => []],
        ],

        // Backend rendering
        'pdf_backend' => 'CPDF',

        // Paper settings
        'default_media_type' => 'screen',
        'default_paper_size' => 'a4',
        'default_paper_orientation' => 'portrait',

        // Display & output
        'dpi' => 96,
        'enable_php' => false,
        'enable_javascript' => true,
        'enable_remote' => false,
        'allowed_remote_hosts' => null,

        // HTML parser
        'enable_html5_parser' => true,

        // Font height ratio
        'font_height_ratio' => 1.1,

        // Enable font subsetting (optional, can reduce file size)
        'enable_font_subsetting' => true,

        // Log file
        'log_output_file' => null,
        'artifactPathValidation' => null,
    ],

];
