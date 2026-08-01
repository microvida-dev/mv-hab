<?php

namespace App\Data\Reports;

final readonly class ApplicationResultExportFileData
{
    public function __construct(
        public string $path,
        public string $mediaType,
        public int $size,
        public string $sha256,
        public int $rowCount,
        public ?string $schema = null,
    ) {}

    /** @return array<string, int|string|null> */
    public function toManifestArray(): array
    {
        return [
            'path' => $this->path,
            'media_type' => $this->mediaType,
            'size' => $this->size,
            'sha256' => $this->sha256,
            'row_count' => $this->rowCount,
            'schema' => $this->schema,
        ];
    }
}
