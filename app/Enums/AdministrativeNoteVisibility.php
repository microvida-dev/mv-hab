<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AdministrativeNoteVisibility: string
{
    use HasOptions;

    case Internal = 'internal';
    case CandidateVisible = 'candidate_visible';
    case AuditOnly = 'audit_only';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Interna',
            self::CandidateVisible => 'Visível ao candidato',
            self::AuditOnly => 'Apenas auditoria',
        };
    }
}
