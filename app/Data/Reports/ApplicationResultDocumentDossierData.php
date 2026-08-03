<?php

namespace App\Data\Reports;

final readonly class ApplicationResultDocumentDossierData
{
    /**
     * @param  list<ApplicationResultExportFileData>  $files
     * @param  list<string>  $warnings
     */
    public function __construct(
        public bool $documentFilesIncluded,
        public array $files,
        public array $warnings,
    ) {}
}
