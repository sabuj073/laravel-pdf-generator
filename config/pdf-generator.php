<?php

return [
    'paper' => env('PDF_PAPER', 'A4'),
    'orientation' => env('PDF_ORIENTATION', 'portrait'),
    'default_font' => env('PDF_FONT', 'DejaVu Sans'),

    'bangla_font' => [
        'enabled' => env('PDF_BANGLA_FONT_ENABLED', true),
        'path' => env('PDF_BANGLA_FONT_PATH'), // null = auto: package resources/fonts, then storage/fonts
        'family' => env('PDF_BANGLA_FONT_FAMILY', 'Noto Sans Bengali'),
    ],
];
