<?php

return [
    'enabled' => env('DOCUMENT_AI_ENABLED', true),
    'queue' => env('DOCUMENT_AI_QUEUE', 'default'),

    'processing' => [
        'check_local_tools' => env('DOCUMENT_AI_CHECK_LOCAL_TOOLS', true),
        'missing_tools_status' => env('DOCUMENT_AI_MISSING_TOOLS_STATUS', 'manual_review'),
    ],

    'classification' => [
        'enabled' => env('DOCUMENT_AI_CLASSIFICATION_ENABLED', true),
        'timeout' => (int) env('DOCUMENT_AI_CLASSIFICATION_TIMEOUT', 120),
    ],

    'ocr' => [
        'enabled' => env('DOCUMENT_AI_OCR_ENABLED', true),
        'driver' => env('DOCUMENT_AI_OCR_DRIVER', 'tesseract'),
        'binary' => env('DOCUMENT_AI_TESSERACT_BINARY', 'tesseract'),
        'language' => env('DOCUMENT_AI_TESSERACT_LANG', 'por+eng'),
        'max_pages' => (int) env('DOCUMENT_AI_OCR_MAX_PAGES', 10),
        'timeout' => (int) env('DOCUMENT_AI_OCR_TIMEOUT', 120),
        'fallback_to_source_text' => env('DOCUMENT_AI_OCR_FALLBACK_TO_SOURCE_TEXT', false),
    ],

    'pdf' => [
        'pdftotext_binary' => env('DOCUMENT_AI_PDFTOTEXT_BINARY', 'pdftotext'),
        'pdfimages_binary' => env('DOCUMENT_AI_PDFIMAGES_BINARY', 'pdfimages'),
        'pdftoppm_binary' => env('DOCUMENT_AI_PDFTOPPM_BINARY', 'pdftoppm'),
    ],

    'image' => [
        'magick_binary' => env('DOCUMENT_AI_MAGICK_BINARY', 'magick'),
    ],

    'ollama' => [
        'enabled' => env('DOCUMENT_AI_OLLAMA_ENABLED', false),
        'base_url' => env('DOCUMENT_AI_OLLAMA_URL', 'http://127.0.0.1:11434'),
        'model' => env('DOCUMENT_AI_OLLAMA_MODEL', 'gemma3:4b'),
        'timeout' => (int) env('DOCUMENT_AI_OLLAMA_TIMEOUT', 120),
    ],
];
