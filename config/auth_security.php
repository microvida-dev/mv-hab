<?php

declare(strict_types=1);

return [
    'rate_limits' => [
        'login_submission' => [
            'attempts' => (int) env(
                'MVHAB_AUTH_LOGIN_SUBMISSION_ATTEMPTS',
                20,
            ),
            'decay_minutes' => (int) env(
                'MVHAB_AUTH_LOGIN_SUBMISSION_DECAY_MINUTES',
                1,
            ),
        ],
        'login_credentials' => [
            'attempts' => (int) env(
                'MVHAB_AUTH_LOGIN_CREDENTIAL_ATTEMPTS',
                5,
            ),
            'decay_seconds' => (int) env(
                'MVHAB_AUTH_LOGIN_CREDENTIAL_DECAY_SECONDS',
                60,
            ),
        ],
        'registration' => [
            'ip_attempts' => (int) env(
                'MVHAB_AUTH_REGISTRATION_IP_ATTEMPTS',
                5,
            ),
            'ip_decay_minutes' => (int) env(
                'MVHAB_AUTH_REGISTRATION_IP_DECAY_MINUTES',
                10,
            ),
            'email_attempts' => (int) env(
                'MVHAB_AUTH_REGISTRATION_EMAIL_ATTEMPTS',
                3,
            ),
            'email_decay_minutes' => (int) env(
                'MVHAB_AUTH_REGISTRATION_EMAIL_DECAY_MINUTES',
                60,
            ),
        ],
        'password_reset' => [
            'ip_attempts' => (int) env(
                'MVHAB_AUTH_PASSWORD_RESET_IP_ATTEMPTS',
                5,
            ),
            'ip_decay_minutes' => (int) env(
                'MVHAB_AUTH_PASSWORD_RESET_IP_DECAY_MINUTES',
                10,
            ),
            'email_attempts' => (int) env(
                'MVHAB_AUTH_PASSWORD_RESET_EMAIL_ATTEMPTS',
                3,
            ),
            'email_decay_minutes' => (int) env(
                'MVHAB_AUTH_PASSWORD_RESET_EMAIL_DECAY_MINUTES',
                10,
            ),
        ],
        'verification_resend' => [
            'attempts' => (int) env(
                'MVHAB_AUTH_VERIFICATION_RESEND_ATTEMPTS',
                6,
            ),
            'decay_minutes' => (int) env(
                'MVHAB_AUTH_VERIFICATION_RESEND_DECAY_MINUTES',
                1,
            ),
        ],
    ],
];
