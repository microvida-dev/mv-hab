<?php

namespace App\Services\Reporting\Temporal;

use App\Services\Support\CanonicalJsonHasher;
use Generator;
use Illuminate\Support\Facades\Storage;
use JsonException;
use RuntimeException;

final class CanonicalNdjsonStore
{
    public function __construct(
        private readonly CanonicalJsonHasher $hasher,
    ) {}

    public function createDirectory(string $directory): void
    {
        $this->assertSafePath($directory);
        $disk = Storage::disk('local');
        if (! $disk->directoryExists($directory) && ! $disk->makeDirectory($directory)) {
            throw new RuntimeException('Não foi possível criar o staging privado da exportação.');
        }
    }

    /**
     * @param  iterable<array<string, mixed>>  $rows
     *
     * @throws JsonException
     */
    public function write(string $path, iterable $rows): int
    {
        $this->assertSafePath($path);
        $absolute = Storage::disk('local')->path($path);
        $directory = dirname($absolute);
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Não foi possível preparar o ficheiro canónico.');
        }

        $stream = fopen($absolute, 'wb');
        if ($stream === false) {
            throw new RuntimeException('Não foi possível abrir o ficheiro canónico.');
        }

        $count = 0;
        try {
            foreach ($rows as $row) {
                $encoded = $this->hasher->encode($row)."\n";
                if (fwrite($stream, $encoded) !== strlen($encoded)) {
                    throw new RuntimeException('A escrita do snapshot canónico ficou incompleta.');
                }
                $count++;
            }
        } finally {
            fclose($stream);
        }

        return $count;
    }

    /**
     * @return Generator<int, array<string, mixed>>
     *
     * @throws JsonException
     */
    public function rows(string $path): Generator
    {
        $this->assertSafePath($path);
        $absolute = Storage::disk('local')->path($path);
        $stream = fopen($absolute, 'rb');
        if ($stream === false) {
            throw new RuntimeException('O snapshot canónico não está disponível.');
        }

        try {
            while (($line = fgets($stream)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $row = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
                if (! is_array($row)) {
                    throw new RuntimeException('O snapshot canónico contém uma linha inválida.');
                }

                /** @var array<string, mixed> $row */
                yield $row;
            }
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $transform
     *
     * @throws JsonException
     */
    public function rewrite(string $path, callable $transform): void
    {
        $temporary = $path.'.rewrite';
        $this->write($temporary, (function () use ($path, $transform): Generator {
            foreach ($this->rows($path) as $row) {
                yield $transform($row);
            }
        })());

        $disk = Storage::disk('local');
        if ($disk->exists($path) && ! $disk->delete($path)) {
            $disk->delete($temporary);
            throw new RuntimeException('Não foi possível substituir o snapshot canónico.');
        }
        if (! $disk->move($temporary, $path)) {
            throw new RuntimeException('Não foi possível concluir o snapshot canónico.');
        }
    }

    public function checksum(string $path): string
    {
        $this->assertSafePath($path);
        $hash = hash_file('sha256', Storage::disk('local')->path($path));
        if (! is_string($hash)) {
            throw new RuntimeException('Não foi possível calcular o checksum canónico.');
        }

        return $hash;
    }

    public function deleteDirectory(string $directory): void
    {
        $this->assertSafePath($directory);
        Storage::disk('local')->deleteDirectory($directory);
    }

    private function assertSafePath(string $path): void
    {
        if (
            $path === ''
            || str_starts_with($path, '/')
            || str_contains($path, '..')
            || preg_match('/^[A-Za-z0-9_\/.\-]+$/', $path) !== 1
        ) {
            throw new RuntimeException('O path privado da exportação não é seguro.');
        }
    }
}
