<?php

return [
    'enabled' => env('DOCUMENT_AI_SCORE_ENABLED', true),
    'queue' => env('DOCUMENT_AI_SCORE_QUEUE', 'default'),

    'weights' => [
        'ocr' => 20,
        'classification' => 20,
        'extraction' => 20,
        'consistency' => 25,
        'risk' => 15,
    ],

    'labels' => [
        'muito_confiavel' => ['min' => 90, 'max' => 100, 'label' => 'Muito confiável'],
        'confiavel_com_atencao' => ['min' => 75, 'max' => 89, 'label' => 'Confiável com atenção'],
        'requer_revisao' => ['min' => 60, 'max' => 74, 'label' => 'Requer revisão'],
        'baixa_confianca' => ['min' => 40, 'max' => 59, 'label' => 'Baixa confiança'],
        'critico' => ['min' => 0, 'max' => 39, 'label' => 'Crítico para revisão'],
    ],

    'thresholds' => [
        'minimum_ocr_text_characters' => 80,
        'minimum_ocr_words' => 12,
        'low_ocr_quality' => 0.55,
        'low_classification_confidence' => 0.70,
        'low_extraction_confidence' => 0.65,
        'manual_review_score_below' => 75,
    ],

    'penalties' => [
        'document_expired' => 20,
        'document_unreadable' => 35,
        'page_cropped' => 20,
        'insufficient_ocr' => 25,
        'nif_mismatch' => 45,
        'name_mismatch' => 35,
        'income_incompatible' => 25,
        'duplicate_document' => 15,
        'empty_document' => 60,
        'missing_required_fields' => 25,
    ],

    'suggestions' => [
        'default_status' => 'draft',
        'auto_send' => false,
    ],
];
