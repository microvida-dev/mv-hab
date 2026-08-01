<?php

namespace App\Services\Reporting\Temporal;

use App\Data\Reports\ApplicationResultExportFileData;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class ApplicationResultExportFileFactory
{
    public function __construct(
        private readonly ApplicationResultExportPathGuard $paths,
    ) {}

    public function make(
        string $storageDirectory,
        string $packagePath,
        string $mediaType,
        int $rowCount,
        ?string $schema = null,
    ): ApplicationResultExportFileData {
        $this->paths->assertRelative($packagePath);
        $storagePath = trim($storageDirectory, '/').'/'.$packagePath;
        $disk = Storage::disk('local');
        if (! $disk->exists($storagePath)) {
            throw new RuntimeException('Um ficheiro declarado da exportação não existe.');
        }

        $sha256 = hash_file('sha256', $disk->path($storagePath));
        if (! is_string($sha256)) {
            throw new RuntimeException('Não foi possível calcular o SHA-256 do ficheiro.');
        }

        return new ApplicationResultExportFileData(
            path: $packagePath,
            mediaType: $mediaType,
            size: $disk->size($storagePath),
            sha256: $sha256,
            rowCount: $rowCount,
            schema: $schema,
        );
    }
}
