<?php

return [
    'enabled' => env('DOCUMENT_AI_VALIDATION_ENABLED', true),
    'queue' => env('DOCUMENT_AI_VALIDATION_QUEUE', env('DOCUMENT_AI_QUEUE', 'default')),

    'thresholds' => [
        'name_similarity_match' => (float) env('DOCUMENT_AI_VALIDATION_NAME_MATCH', 0.92),
        'name_similarity_partial' => (float) env('DOCUMENT_AI_VALIDATION_NAME_PARTIAL', 0.80),
        'money_light_tolerance_percent' => (float) env('DOCUMENT_AI_VALIDATION_MONEY_LIGHT_TOLERANCE', 5),
        'money_medium_tolerance_percent' => (float) env('DOCUMENT_AI_VALIDATION_MONEY_MEDIUM_TOLERANCE', 15),
        'critical_income_difference_percent' => (float) env('DOCUMENT_AI_VALIDATION_CRITICAL_INCOME_DIFF', 25),
        'address_similarity_match' => (float) env('DOCUMENT_AI_VALIDATION_ADDRESS_MATCH', 0.85),
        'address_similarity_partial' => (float) env('DOCUMENT_AI_VALIDATION_ADDRESS_PARTIAL', 0.68),
    ],

    'store_plain_values' => env('DOCUMENT_AI_VALIDATION_STORE_PLAIN_VALUES', true),
    'hash_values' => env('DOCUMENT_AI_VALIDATION_HASH_VALUES', true),
];
