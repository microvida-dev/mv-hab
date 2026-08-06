<?php

declare(strict_types=1);

return [
    'enabled' => env('TURNSTILE_ENABLED', false),
    'site_key' => env('TURNSTILE_SITE_KEY'),
    'secret_key' => env('TURNSTILE_SECRET_KEY'),
    'expected_hostname' => env('TURNSTILE_EXPECTED_HOSTNAME'),
    'action' => env('TURNSTILE_LOGIN_ACTION', 'login'),
    'verify_url' => env(
        'TURNSTILE_VERIFY_URL',
        'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ),
    'timeout_seconds' => (int) env('TURNSTILE_TIMEOUT', 5),
];
