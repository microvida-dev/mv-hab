<?php

declare(strict_types=1);

return [
    'enabled' => env('MVHAB_PUBLIC_VISITS_ENABLED', true),
    'queue' => env('MVHAB_PUBLIC_VISITS_QUEUE', 'communications'),
    'max_guests_per_booking' => (int) env(
        'MVHAB_PUBLIC_VISITS_MAX_GUESTS',
        6,
    ),
    'rate_limit' => [
        'attempts' => (int) env(
            'MVHAB_PUBLIC_VISITS_RATE_LIMIT_ATTEMPTS',
            5,
        ),
        'decay_seconds' => (int) env(
            'MVHAB_PUBLIC_VISITS_RATE_LIMIT_DECAY',
            600,
        ),
    ],
    'cancellation_cutoff_minutes' => (int) env(
        'MVHAB_PUBLIC_VISITS_CANCELLATION_CUTOFF_MINUTES',
        60,
    ),
    'retention_months' => (int) env(
        'MVHAB_PUBLIC_VISITS_RETENTION_MONTHS',
        6,
    ),
    'privacy_notice_version' => env(
        'MVHAB_PUBLIC_VISITS_PRIVACY_NOTICE_VERSION',
        '2026-07-30',
    ),
    'turnstile' => [
        'enabled' => env(
            'MVHAB_PUBLIC_VISITS_TURNSTILE_ENABLED',
            false,
        ),
        'site_key' => env(
            'MVHAB_PUBLIC_VISITS_TURNSTILE_SITE_KEY',
        ),
        'secret_key' => env(
            'MVHAB_PUBLIC_VISITS_TURNSTILE_SECRET_KEY',
        ),
        'verify_url' => env(
            'MVHAB_PUBLIC_VISITS_TURNSTILE_VERIFY_URL',
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        ),
        'timeout_seconds' => (int) env(
            'MVHAB_PUBLIC_VISITS_TURNSTILE_TIMEOUT',
            5,
        ),
    ],
];
