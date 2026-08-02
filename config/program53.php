<?php

return [
    'benchmark' => [
        'allowed_environments' => ['local', 'testing', 'benchmark'],
        'max_applications' => 50_000,
        'chunk_size' => 50,
        'memory_guardrail_bytes' => 512 * 1024 * 1024,
        'minimum_free_disk_bytes' => 512 * 1024 * 1024,
        'output_directory' => 'storage/qa',
    ],
    'queues' => [
        'reports' => [
            'timeout' => 1800,
            'retry_after' => 2100,
            'tries' => 3,
            'backoff' => [60, 300, 900],
        ],
        'notifications' => [
            'timeout' => 120,
            'retry_after' => 180,
            'tries' => 5,
            'backoff' => [30, 120, 300, 900],
        ],
    ],
    'exports' => [
        'retention_days' => 7,
        'stale_after_seconds' => 2100,
    ],
];
