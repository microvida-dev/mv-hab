<?php

namespace App\Services\Reporting\Temporal;

use App\Contracts\Program53\Program53FaultInjector;
use App\Data\Program53\Program53OperationalContext;
use App\Data\Reports\ApplicationResultExportFileData;
use App\Data\Reports\ApplicationResultExportPackageData;
use App\Data\Reports\ApplicationResultExportPackageOptionsData;
use App\Data\Reports\ApplicationResultExportSnapshotData;
use App\Enums\ApplicationResultExportDataset;
use App\Services\Reporting\Temporal\Exporters\ApplicationResultExportWriterRegistry;
use App\Services\Support\CanonicalJsonHasher;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use ZipArchive;

final class ApplicationResultExportPackageBuilder
{
    public function __construct(
        private readonly ApplicationResultExportWriterRegistry $writers,
        private readonly ApplicationResultExportMetadataFactory $metadata,
        private readonly ApplicationResultDocumentDossierBuilder $dossier,
        private readonly ApplicationResultExportManifestBuilder $manifests,
        private readonly ApplicationResultExportFileFactory $files,
        private readonly ApplicationResultExportPathGuard $paths,
        private readonly CanonicalJsonHasher $hasher,
        private readonly Program53FaultInjector $faults,
    ) {}

    public function build(
        ApplicationResultExportSnapshotData $snapshot,
        ApplicationResultExportPackageOptionsData $options,
        string $stagingDirectory,
        ?Program53OperationalContext $context = null,
    ): ApplicationResultExportPackageData {
        $this->validateOptions($snapshot, $options);
        $this->paths->assertRelative($stagingDirectory);
        $disk = Storage::disk('local');
        $disk->deleteDirectory($stagingDirectory);
        $contentsDirectory = $stagingDirectory.'/contents';
        if (! $disk->makeDirectory($contentsDirectory)) {
            throw new RuntimeException('Não foi possível preparar o pacote municipal.');
        }

        try {
            $metadata = $this->metadata->build($snapshot, $options);
            $files = [];
            foreach ($options->formats as $format) {
                $files = [
                    ...$files,
                    ...$this->writers->get($format)->write(
                        $snapshot,
                        $options,
                        $contentsDirectory,
                        $metadata,
                    ),
                ];
                if ($context instanceof Program53OperationalContext) {
                    $this->faults->checkpoint(
                        'after_'.$format->value,
                        $context->withStage('rendering_'.$format->value),
                    );
                }
            }

            $files = [...$files, ...$this->copySchemas($contentsDirectory)];
            $dossier = $this->dossier->build($snapshot, $options, $contentsDirectory);
            $files = [...$files, ...$dossier->files];
            $this->assertUniquePaths($files);

            $manifest = $this->manifests->build(
                $snapshot,
                $options,
                $files,
                $dossier->documentFilesIncluded,
                $dossier->warnings,
            );
            $manifestPath = $contentsDirectory.'/manifest.json';
            $manifestContents = $this->hasher->encode($manifest)."\n";
            if (! $disk->put($manifestPath, $manifestContents)) {
                throw new RuntimeException('Não foi possível escrever o manifesto municipal.');
            }
            $manifestSha256 = hash('sha256', $manifestContents);
            if ($context instanceof Program53OperationalContext) {
                $this->faults->checkpoint(
                    'after_manifest',
                    $context->withStage('manifest'),
                );
            }

            $this->writeChecksums(
                $contentsDirectory,
                $files,
                $manifestSha256,
            );
            $entries = array_map(
                static fn (ApplicationResultExportFileData $file): string => $file->path,
                $files,
            );
            $entries[] = 'manifest.json';
            $entries[] = 'checksums.sha256';
            sort($entries, SORT_STRING);

            $fileName = $this->packageFileName($snapshot);
            $packagePath = $stagingDirectory.'/'.$fileName;
            $this->createZip(
                $contentsDirectory,
                $packagePath,
                $entries,
                $snapshot->source->snapshotAt->getTimestamp(),
            );
            if ($context instanceof Program53OperationalContext) {
                $this->faults->checkpoint(
                    'after_partial_zip',
                    $context->withStage('package'),
                );
            }
            $this->validateZip($packagePath, $entries);
            $packageSha256 = hash_file('sha256', $disk->path($packagePath));
            if (! is_string($packageSha256)) {
                throw new RuntimeException('Não foi possível calcular o hash do pacote municipal.');
            }

            $disk->deleteDirectory($contentsDirectory);

            return new ApplicationResultExportPackageData(
                packagePath: $packagePath,
                fileName: $fileName,
                size: $disk->size($packagePath),
                manifestSha256: $manifestSha256,
                packageSha256: $packageSha256,
                documentFilesIncluded: $dossier->documentFilesIncluded,
                files: $files,
                warnings: $manifest['warnings'],
            );
        } catch (Throwable $exception) {
            $disk->deleteDirectory($stagingDirectory);

            throw $exception;
        }
    }

    private function validateOptions(
        ApplicationResultExportSnapshotData $snapshot,
        ApplicationResultExportPackageOptionsData $options,
    ): void {
        if (
            in_array(ApplicationResultExportDataset::Changes, $options->datasets, true)
            && ! $snapshot->source->mode->isDelta()
        ) {
            throw new RuntimeException('O dataset de alterações só existe nos modos delta.');
        }

        if ($options->changedDocumentsOnly && ! $snapshot->source->mode->isDelta()) {
            throw new RuntimeException('A seleção de documentos alterados exige um modo delta.');
        }

    }

    /** @return list<ApplicationResultExportFileData> */
    private function copySchemas(string $contentsDirectory): array
    {
        $schemaDirectory = $contentsDirectory.'/schema';
        $disk = Storage::disk('local');
        if (! $disk->makeDirectory($schemaDirectory)) {
            throw new RuntimeException('Não foi possível criar a diretoria de schemas.');
        }

        $definitions = [
            ApplicationResultExportSchemaValidator::JSON_SCHEMA => [
                'schema/mvhab-application-results-v1.schema.json',
                'application/schema+json',
            ],
            ApplicationResultExportSchemaValidator::XML_SCHEMA => [
                'schema/mvhab-application-results-v1.xsd',
                'application/xml',
            ],
        ];
        $files = [];
        foreach ($definitions as $source => [$packagePath, $mediaType]) {
            $contents = file_get_contents(base_path($source));
            if (! is_string($contents) || ! $disk->put($contentsDirectory.'/'.$packagePath, $contents)) {
                throw new RuntimeException('Não foi possível incluir um schema versionado.');
            }
            $files[] = $this->files->make(
                $contentsDirectory,
                $packagePath,
                $mediaType,
                0,
            );
        }

        return $files;
    }

    /** @param list<ApplicationResultExportFileData> $files */
    private function writeChecksums(
        string $contentsDirectory,
        array $files,
        string $manifestSha256,
    ): void {
        $checksums = ['manifest.json' => $manifestSha256];
        foreach ($files as $file) {
            $checksums[$file->path] = $file->sha256;
        }
        ksort($checksums, SORT_STRING);

        $contents = '';
        foreach ($checksums as $path => $sha256) {
            $contents .= $sha256.'  '.$path."\n";
        }

        if (! Storage::disk('local')->put(
            $contentsDirectory.'/checksums.sha256',
            $contents,
        )) {
            throw new RuntimeException('Não foi possível escrever os checksums do pacote.');
        }
    }

    /**
     * @param  list<string>  $entries
     */
    private function createZip(
        string $contentsDirectory,
        string $packagePath,
        array $entries,
        int $timestamp,
    ): void {
        $disk = Storage::disk('local');
        $temporaryPath = $packagePath.'.partial';
        $zip = new ZipArchive;
        if ($zip->open($disk->path($temporaryPath), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Não foi possível iniciar o pacote ZIP.');
        }

        try {
            foreach ($entries as $entry) {
                $this->paths->assertRelative($entry);
                $absolutePath = $disk->path($contentsDirectory.'/'.$entry);
                if (! is_file($absolutePath) || is_link($absolutePath)) {
                    throw new RuntimeException('O pacote contém uma origem de ficheiro inválida.');
                }
                if (! $zip->addFile($absolutePath, $entry)) {
                    throw new RuntimeException('Não foi possível adicionar um ficheiro ao pacote.');
                }
                $zip->setCompressionName($entry, ZipArchive::CM_DEFLATE, 6);
                $zip->setMtimeName($entry, max($timestamp, 315532800));
            }
        } finally {
            if (! $zip->close()) {
                throw new RuntimeException('Não foi possível concluir o pacote ZIP.');
            }
        }

        if (! rename($disk->path($temporaryPath), $disk->path($packagePath))) {
            throw new RuntimeException('Não foi possível publicar atomicamente o pacote ZIP.');
        }
    }

    /** @param list<string> $expectedEntries */
    private function validateZip(string $packagePath, array $expectedEntries): void
    {
        $zip = new ZipArchive;
        if ($zip->open(Storage::disk('local')->path($packagePath)) !== true) {
            throw new RuntimeException('O pacote ZIP final não pode ser aberto.');
        }

        try {
            $actual = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                if (! is_string($name)) {
                    throw new RuntimeException('O pacote ZIP possui uma entrada inválida.');
                }
                $this->paths->assertRelative($name);
                $actual[] = $name;
            }
            sort($actual, SORT_STRING);
            sort($expectedEntries, SORT_STRING);
            if ($actual !== $expectedEntries) {
                throw new RuntimeException('O conteúdo do pacote ZIP diverge do manifesto técnico.');
            }
        } finally {
            $zip->close();
        }
    }

    /** @param list<ApplicationResultExportFileData> $files */
    private function assertUniquePaths(array $files): void
    {
        $seen = [];
        foreach ($files as $file) {
            $this->paths->assertRelative($file->path);
            if (isset($seen[$file->path])) {
                throw new RuntimeException('O pacote contém nomes de ficheiro duplicados.');
            }
            $seen[$file->path] = true;
        }
    }

    private function packageFileName(ApplicationResultExportSnapshotData $snapshot): string
    {
        return sprintf(
            'export-%s-%s-%s.zip',
            $this->paths->segment($snapshot->source->contestCode, 'concurso'),
            $snapshot->source->mode->value,
            $snapshot->source->snapshotAt->utc()->format('Ymd-His'),
        );
    }
}
