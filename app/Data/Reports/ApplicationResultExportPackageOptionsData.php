<?php

namespace App\Data\Reports;

use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class ApplicationResultExportPackageOptionsData
{
    /**
     * @param  list<ApplicationResultExportFormat>  $formats
     * @param  list<ApplicationResultExportDataset>  $datasets
     */
    public function __construct(
        public string $exportPublicId,
        public array $formats,
        public array $datasets,
        public CarbonImmutable $generatedAt,
        public CarbonImmutable $expiresAt,
        public bool $includeSensitive = false,
        public bool $sensitiveConfirmed = false,
        public bool $includeDocumentFiles = false,
        public bool $changedDocumentsOnly = false,
        public string $csvDelimiter = ';',
        public bool $csvBom = true,
    ) {
        if ($formats === [] || $datasets === []) {
            throw new InvalidArgumentException('A exportação exige formatos e datasets explícitos.');
        }

        if (count(array_unique(array_map(
            static fn (ApplicationResultExportFormat $format): string => $format->value,
            $formats,
        ))) !== count($formats)) {
            throw new InvalidArgumentException('A lista de formatos contém duplicados.');
        }

        if (count(array_unique(array_map(
            static fn (ApplicationResultExportDataset $dataset): string => $dataset->value,
            $datasets,
        ))) !== count($datasets)) {
            throw new InvalidArgumentException('A lista de datasets contém duplicados.');
        }

        if (! in_array($csvDelimiter, [',', ';', "\t"], true)) {
            throw new InvalidArgumentException('O delimitador CSV não é suportado.');
        }

        if ($includeSensitive && ! $sensitiveConfirmed) {
            throw new InvalidArgumentException('Os campos sensíveis exigem confirmação explícita.');
        }

        if ($includeDocumentFiles && ! $includeSensitive) {
            throw new InvalidArgumentException('O dossier documental exige exportação sensível confirmada.');
        }

        if (! $generatedAt->lessThan($expiresAt)) {
            throw new InvalidArgumentException('A expiração da exportação deve ser posterior à geração.');
        }
    }
}
