#!/usr/bin/env php
<?php

declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php scripts/phpstan-baseline-compare.php <previous-json> <current-json>\n");
    exit(2);
}

[$script, $previousPath, $currentPath] = $argv;

unset($script);

/**
 * @return array<string, mixed>
 */
function read_phpstan_json(string $path): array
{
    if (! is_file($path)) {
        fwrite(STDERR, "PHPStan JSON file not found: {$path}\n");
        exit(2);
    }

    $payload = json_decode((string) file_get_contents($path), true);

    if (! is_array($payload)) {
        fwrite(STDERR, "Invalid PHPStan JSON file: {$path}\n");
        exit(2);
    }

    return $payload;
}

/**
 * @param  array<string, mixed>  $payload
 * @return array<string, array{file: string, line: int, identifier: string, message: string}>
 */
function phpstan_signatures(array $payload): array
{
    $files = $payload['error_details'] ?? $payload['files'] ?? [];

    if (! is_array($files)) {
        return [];
    }

    $signatures = [];
    foreach ($files as $file => $messages) {
        if (! is_string($file) || ! is_array($messages)) {
            continue;
        }

        if (isset($messages['messages']) && is_array($messages['messages'])) {
            $messages = $messages['messages'];
        }

        foreach ($messages as $message) {
            if (! is_array($message)) {
                continue;
            }

            $line = is_int($message['line'] ?? null) ? $message['line'] : 0;
            $identifier = is_string($message['identifier'] ?? null) ? $message['identifier'] : 'unknown';
            $text = is_string($message['message'] ?? null) ? $message['message'] : '';
            $signature = $file.'|'.$identifier.'|'.$text;

            $signatures[$signature] = [
                'file' => $file,
                'line' => $line,
                'identifier' => $identifier,
                'message' => $text,
            ];
        }
    }

    return $signatures;
}

$previous = read_phpstan_json($previousPath);
$current = read_phpstan_json($currentPath);

$previousSignatures = phpstan_signatures($previous);
$currentSignatures = phpstan_signatures($current);

$new = array_diff_key($currentSignatures, $previousSignatures);
$fixed = array_diff_key($previousSignatures, $currentSignatures);
$previousTotal = count($previousSignatures);
$currentTotal = count($currentSignatures);

$summary = [
    'previous' => $previousPath,
    'current' => $currentPath,
    'previous_errors' => $previous['errors'] ?? $previousTotal,
    'current_errors' => $current['errors'] ?? $currentTotal,
    'previous_normalized_errors' => $previousTotal,
    'current_normalized_errors' => $currentTotal,
    'fixed' => count($fixed),
    'new' => count($new),
    'status' => $currentTotal <= $previousTotal && count($new) === 0 ? 'passed' : 'failed',
];

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;

if ($new !== []) {
    fwrite(STDERR, "New PHPStan errors:\n");
    foreach ($new as $error) {
        fwrite(STDERR, "{$error['file']}:{$error['line']} [{$error['identifier']}] {$error['message']}\n");
    }
}

exit($summary['status'] === 'passed' ? 0 : 1);
