<?php

namespace App\Services\Security;

use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;

class DocumentStorageSecurityReviewService
{
    /**
     * @return array{
     *     documents: int,
     *     public_storage_versions: int,
     *     versions_without_checksum: int,
     *     filenames_with_possible_personal_data: int,
     *     status: string,
     *     recommendations: list<string>
     * }
     */
    public function review(): array
    {
        $publicVersions = DocumentVersion::query()->where('storage_disk', 'public')->count();
        $missingChecksums = DocumentVersion::query()->whereNull('checksum')->count();
        $riskyNames = DocumentVersion::query()
            ->whereNotNull('original_filename')
            ->pluck('original_filename')
            ->filter(fn (string $filename): bool => str_contains($filename, '@') || preg_match('/\d{9}/', $filename) === 1)
            ->count();

        return [
            'documents' => DocumentSubmission::query()->count(),
            'public_storage_versions' => $publicVersions,
            'versions_without_checksum' => $missingChecksums,
            'filenames_with_possible_personal_data' => $riskyNames,
            'status' => $publicVersions === 0 ? 'passed' : 'requires_action',
            'recommendations' => [
                'Manter downloads por controller autorizado.',
                'Não expor storage_path em views públicas.',
                'Rever nomes de ficheiro com dados pessoais antes de produção.',
            ],
        ];
    }
}
