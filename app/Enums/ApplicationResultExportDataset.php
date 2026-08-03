<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationResultExportDataset: string
{
    use HasOptions;

    case Applications = 'applications';
    case Documents = 'documents';
    case Findings = 'findings';
    case Changes = 'changes';

    public function label(): string
    {
        return match ($this) {
            self::Applications => 'Candidaturas',
            self::Documents => 'Documentos',
            self::Findings => 'Achados',
            self::Changes => 'Alterações',
        };
    }
}
