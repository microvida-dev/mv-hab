#!/usr/bin/env php
<?php

declare(strict_types=1);

if ($argc !== 2) {
    fwrite(STDERR, "Usage: php scripts/phpstan-count-errors.php <phpstan-json>\n");
    exit(2);
}

$path = $argv[1];

if (! is_file($path)) {
    fwrite(STDERR, "PHPStan JSON file not found: {$path}\n");
    exit(2);
}

$payload = json_decode((string) file_get_contents($path), true);

if (! is_array($payload)) {
    fwrite(STDERR, "Invalid PHPStan JSON file: {$path}\n");
    exit(2);
}

/**
 * @param  array<string, mixed>  $payload
 * @return list<array{file: string, line: int, identifier: string, message: string}>
 */
function phpstan_errors(array $payload): array
{
    $files = $payload['error_details'] ?? $payload['files'] ?? [];

    if (! is_array($files)) {
        return [];
    }

    $errors = [];
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

            $errors[] = [
                'file' => $file,
                'line' => is_int($message['line'] ?? null) ? $message['line'] : 0,
                'identifier' => is_string($message['identifier'] ?? null) ? $message['identifier'] : 'unknown',
                'message' => is_string($message['message'] ?? null) ? $message['message'] : '',
            ];
        }
    }

    return $errors;
}

$errors = phpstan_errors($payload);
$files = [];
$identifiers = [];

foreach ($errors as $error) {
    $files[$error['file']] = true;
    $identifiers[$error['identifier']] = ($identifiers[$error['identifier']] ?? 0) + 1;
}

arsort($identifiers);

echo json_encode([
    'file' => $path,
    'wrapper_errors' => $payload['errors'] ?? count($errors),
    'normalized_errors' => count($errors),
    'files' => count($files),
    'identifiers' => $identifiers,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
