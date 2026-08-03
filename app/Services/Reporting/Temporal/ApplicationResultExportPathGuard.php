<?php

namespace App\Services\Reporting\Temporal;

use Illuminate\Support\Str;
use RuntimeException;

final class ApplicationResultExportPathGuard
{
    public function assertRelative(string $path): void
    {
        if (
            $path === ''
            || str_starts_with($path, '/')
            || str_contains($path, "\0")
            || str_contains($path, '\\')
            || preg_match('/^[A-Za-z]:/', $path) === 1
        ) {
            throw new RuntimeException('O path do pacote não é relativo e seguro.');
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new RuntimeException('O path do pacote contém um segmento inválido.');
            }
        }
    }

    public function segment(string $value, string $fallback = 'export'): string
    {
        $segment = Str::slug($value, '-');
        $segment = trim($segment, '.-');

        return $segment === '' ? $fallback : $segment;
    }
}
