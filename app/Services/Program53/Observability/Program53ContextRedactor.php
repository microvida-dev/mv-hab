<?php

namespace App\Services\Program53\Observability;

final class Program53ContextRedactor
{
    /** @var list<string> */
    private const ALLOWED_KEYS = [
        'event',
        'metric',
        'value',
        'operation_id',
        'request_id',
        'correlation_id',
        'municipality_id',
        'contest_id',
        'batch_id',
        'publication_id',
        'correction_request_id',
        'export_id',
        'job_id',
        'attempt',
        'stage',
        'status',
        'duration_ms',
        'failure_code',
        'counts',
        'labels',
    ];

    /** @var list<string> */
    private const ALLOWED_LABELS = [
        'component',
        'operation',
        'stage',
        'status',
        'failure_code',
        'format',
        'dataset',
        'result',
        'reused',
    ];

    /** @param array<string, mixed> $context
     * @return array<string, bool|float|int|string|array<string, bool|float|int|string>>
     */
    public function redact(array $context): array
    {
        $safe = [];

        foreach (self::ALLOWED_KEYS as $key) {
            if (! array_key_exists($key, $context) || $context[$key] === null) {
                continue;
            }

            if ($key === 'labels') {
                $labels = $this->safeMap($context[$key], self::ALLOWED_LABELS);
                if ($labels !== []) {
                    $safe[$key] = $labels;
                }

                continue;
            }

            if ($key === 'counts') {
                $counts = $this->safeMap($context[$key]);
                if ($counts !== []) {
                    $safe[$key] = $counts;
                }

                continue;
            }

            $value = $this->safeScalar($context[$key]);
            if ($value !== null) {
                $safe[$key] = $value;
            }
        }

        return $safe;
    }

    /**
     * @param  list<string>|null  $allowed
     * @return array<string, bool|float|int|string>
     */
    private function safeMap(mixed $value, ?array $allowed = null): array
    {
        if (! is_array($value)) {
            return [];
        }

        $safe = [];
        foreach ($value as $key => $item) {
            if (
                ! is_string($key)
                || preg_match('/^[a-z][a-z0-9_]{0,63}$/', $key) !== 1
                || ($allowed !== null && ! in_array($key, $allowed, true))
            ) {
                continue;
            }

            $scalar = $this->safeScalar($item);
            if ($scalar !== null) {
                $safe[$key] = $scalar;
            }
        }

        ksort($safe, SORT_STRING);

        return $safe;
    }

    private function safeScalar(mixed $value): bool|float|int|string|null
    {
        if (is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        return mb_substr($value, 0, 160);
    }
}
