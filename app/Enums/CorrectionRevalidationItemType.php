<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CorrectionRevalidationItemType: string
{
    use HasOptions;

    case UnchangedValid = 'unchanged_valid';
    case ChangedDocument = 'changed_document';
    case NewDocument = 'new_document';
    case ReplacedDocument = 'replaced_document';
    case CandidateJustification = 'candidate_justification';
    case DependencyAffected = 'dependency_affected';

    public function label(): string
    {
        return match ($this) {
            self::UnchangedValid => 'Validação anterior mantida',
            self::ChangedDocument => 'Documento alterado',
            self::NewDocument => 'Novo documento',
            self::ReplacedDocument => 'Documento substituído',
            self::CandidateJustification => 'Justificação do candidato',
            self::DependencyAffected => 'Dependência afetada',
        };
    }

    public function isReviewable(): bool
    {
        return $this !== self::UnchangedValid;
    }
}
