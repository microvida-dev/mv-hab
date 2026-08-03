<?php

namespace App\Services\Program53\Benchmark;

use App\Data\Program53\Program53BenchmarkConfiguration;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class Program53BenchmarkEnvironment
{
    private ?string $originalLocalRoot = null;

    public function assertAllowed(): void
    {
        $environment = (string) app()->environment();
        $allowed = config('program53.benchmark.allowed_environments', [
            'local',
            'testing',
            'benchmark',
        ]);

        if (
            $environment === 'production'
            || ! is_array($allowed)
            || ! in_array($environment, $allowed, true)
        ) {
            throw new RuntimeException(
                'O benchmark Programa 53 só pode correr num ambiente local, testing ou benchmark.',
            );
        }
    }

    public function runId(Program53BenchmarkConfiguration $configuration): string
    {
        return sprintf(
            '%s-%d-%s',
            $configuration->scenario,
            $configuration->applications,
            substr(hash('sha256', implode(':', [
                $configuration->seed,
                $configuration->applications,
                $configuration->municipalities,
                $configuration->contests,
            ])), 0, 12),
        );
    }

    public function baseDirectory(string $runId): string
    {
        return storage_path('framework/testing/program53-benchmark/'.$runId);
    }

    public function databasePath(string $runId): string
    {
        return $this->baseDirectory($runId).'/program53-benchmark.sqlite';
    }

    public function storageRoot(string $runId): string
    {
        return $this->baseDirectory($runId).'/storage';
    }

    public function prepare(string $runId): void
    {
        $this->assertAllowed();
        $base = $this->baseDirectory($runId);
        if (is_dir($base)) {
            $this->deleteDirectory($base);
        }
        if (! mkdir($base, 0700, true) && ! is_dir($base)) {
            throw new RuntimeException('Não foi possível criar o ambiente isolado do benchmark.');
        }

        $storage = $this->storageRoot($runId);
        if (! mkdir($storage, 0700, true) && ! is_dir($storage)) {
            throw new RuntimeException('Não foi possível criar o storage isolado do benchmark.');
        }

        $free = disk_free_space($base);
        $minimum = (int) config(
            'program53.benchmark.minimum_free_disk_bytes',
            512 * 1024 * 1024,
        );
        if ($free !== false && $free < $minimum) {
            throw new RuntimeException('O filesystem não possui espaço mínimo para o benchmark isolado.');
        }
    }

    public function activateStorage(string $runId): void
    {
        $root = config('filesystems.disks.local.root');
        $this->originalLocalRoot = is_string($root) ? $root : null;
        config(['filesystems.disks.local.root' => $this->storageRoot($runId)]);
        Storage::forgetDisk('local');
    }

    public function restoreStorage(): void
    {
        if ($this->originalLocalRoot !== null) {
            config(['filesystems.disks.local.root' => $this->originalLocalRoot]);
        }
        Storage::forgetDisk('local');
        $this->originalLocalRoot = null;
    }

    public function cleanup(string $runId): void
    {
        $base = $this->baseDirectory($runId);
        $testingRoot = realpath(storage_path('framework/testing'));
        $parent = realpath(dirname($base));
        if (
            $testingRoot === false
            || $parent === false
            || ! str_starts_with($parent, $testingRoot)
        ) {
            throw new RuntimeException('A limpeza recusou um path fora da área de benchmark.');
        }

        $this->deleteDirectory($base);
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory,
                \FilesystemIterator::SKIP_DOTS,
            ),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if ($item->isLink() || $item->isFile()) {
                if (! unlink($item->getPathname())) {
                    throw new RuntimeException('Não foi possível remover um artefacto do benchmark.');
                }
            } elseif (! rmdir($item->getPathname())) {
                throw new RuntimeException('Não foi possível remover uma diretoria do benchmark.');
            }
        }
        if (! rmdir($directory)) {
            throw new RuntimeException('Não foi possível concluir a limpeza do benchmark.');
        }
    }
}
