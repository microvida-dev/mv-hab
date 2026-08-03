<?php

namespace App\Services\Reporting\Temporal;

use App\Data\Reports\ApplicationResultExportSnapshotData;
use App\Data\Reports\ApplicationResultExportSourceData;
use App\Enums\ApplicationResultExportDataset;
use App\Services\Support\CanonicalJsonHasher;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ApplicationResultExportCheckpointStore
{
    public function __construct(
        private readonly CanonicalNdjsonStore $store,
        private readonly CanonicalJsonHasher $hasher,
    ) {}

    /** @return array<string, mixed> */
    public function capture(ApplicationResultExportSnapshotData $snapshot): array
    {
        return [
            'schema_version' => '1.0',
            'source_payload_sha256' => $this->hasher->hash(
                $snapshot->source->fingerprintPayload(),
            ),
            'dataset_paths' => $snapshot->datasetPaths,
            'counts' => $snapshot->counts,
            'checksums' => $snapshot->checksums,
            'source_fingerprint' => $snapshot->sourceFingerprint,
            'warnings' => $snapshot->warnings,
            'validated_at' => now()->utc()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $checkpoint
     */
    public function restore(
        ApplicationResultExportSourceData $source,
        string $stagingDirectory,
        array $checkpoint,
    ): ?ApplicationResultExportSnapshotData {
        try {
            if (
                ($checkpoint['schema_version'] ?? null) !== '1.0'
                || ! hash_equals(
                    $this->hasher->hash($source->fingerprintPayload()),
                    (string) ($checkpoint['source_payload_sha256'] ?? ''),
                )
            ) {
                return $this->invalidate($stagingDirectory);
            }

            $paths = $this->stringMap($checkpoint['dataset_paths'] ?? null);
            $checksums = $this->stringMap($checkpoint['checksums'] ?? null);
            $counts = $this->integerMap($checkpoint['counts'] ?? null);
            $expectedPaths = $this->datasetPaths($stagingDirectory);
            if ($paths !== $expectedPaths || array_keys($checksums) !== array_keys($expectedPaths)) {
                return $this->invalidate($stagingDirectory);
            }

            foreach ($expectedPaths as $dataset => $path) {
                if (
                    ! Storage::disk('local')->exists($path)
                    || ! isset($checksums[$dataset], $counts[$dataset])
                    || ! hash_equals($checksums[$dataset], $this->store->checksum($path))
                    || $this->rowCount($path) !== $counts[$dataset]
                ) {
                    return $this->invalidate($stagingDirectory);
                }
            }

            $fingerprint = (string) ($checkpoint['source_fingerprint'] ?? '');
            if (preg_match('/^[a-f0-9]{64}$/', $fingerprint) !== 1) {
                return $this->invalidate($stagingDirectory);
            }

            return new ApplicationResultExportSnapshotData(
                source: $source,
                datasetPaths: $paths,
                counts: $counts,
                checksums: $checksums,
                sourceFingerprint: $fingerprint,
                warnings: $this->stringList($checkpoint['warnings'] ?? null),
            );
        } catch (Throwable) {
            return $this->invalidate($stagingDirectory);
        }
    }

    private function rowCount(string $path): int
    {
        $count = 0;
        foreach ($this->store->rows($path) as $row) {
            unset($row);
            $count++;
        }

        return $count;
    }

    /** @return array<string, string> */
    private function datasetPaths(string $directory): array
    {
        $paths = [];
        foreach (ApplicationResultExportDataset::cases() as $dataset) {
            $paths[$dataset->value] = $directory.'/'.$dataset->value.'.ndjson';
        }
        ksort($paths, SORT_STRING);

        return $paths;
    }

    /** @return array<string, string> */
    private function stringMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $map = [];
        foreach ($value as $key => $item) {
            if (is_string($key) && is_string($item)) {
                $map[$key] = $item;
            }
        }
        ksort($map, SORT_STRING);

        return $map;
    }

    /** @return array<string, int> */
    private function integerMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $map = [];
        foreach ($value as $key => $item) {
            if (is_string($key) && is_int($item) && $item >= 0) {
                $map[$key] = $item;
            }
        }
        ksort($map, SORT_STRING);

        return $map;
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $item): bool => is_string($item),
        ));
    }

    private function invalidate(string $directory): null
    {
        $this->store->deleteDirectory($directory);

        return null;
    }
}
