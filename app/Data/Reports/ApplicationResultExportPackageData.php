<?php

namespace App\Data\Reports;

final readonly class ApplicationResultExportPackageData
{
    /**
     * @param  list<ApplicationResultExportFileData>  $files
     * @param  list<string>  $warnings
     */
    public function __construct(
        public string $packagePath,
        public string $fileName,
        public int $size,
        public string $manifestSha256,
        public string $packageSha256,
        public bool $documentFilesIncluded,
        public array $files,
        public array $warnings,
    ) {}
}
