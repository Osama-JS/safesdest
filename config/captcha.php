<?php

return [
    'disable' => env('CAPTCHA_DISABLE', false),
    'characters' => ['0', '1', '2', '3', '4', '6', '7', '8', '9'],
    'default' => [
        'length' => 5,
        'width' => 120,
        'height' => 36,
        'quality' => 90,
        'sensitive' => true,
        'angle' => 12,
        'sharpen' => 10,
        'fontColors' => ['#000000'],
        'blur' => 2,
        'invert' => true,
        'contrast' => -5,
    ],
    'math' => [
        'length' => 9,
        'width' => 120,
        'height' => 36,
        'quality' => 90,
        'math' => true,
    ],

    'flat' => [
        'length' => 5,
        'width' => 160,
        'height' => 50,
        'quality' => 90,
        'lines' => 3,
        'bgImage' => false,
        'bgColor' => '#ecf2f4',
        'fontColors' => ['#000000'],
        'contrast' => -5,
    ],
    'mini' => [
        'length' => 3,
        'width' => 60,
        'height' => 32,
    ],
    'inverse' => [
        'length' => 5,
        'width' => 120,
        'height' => 36,
        'quality' => 90,
        'sensitive' => true,
        'angle' => 12,
        'sharpen' => 10,
        'blur' => 2,
        'invert' => true,
        'contrast' => -5,
    ]
];
