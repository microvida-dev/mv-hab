<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EligibilityCheckType: string
{
    use HasOptions;

    case CandidatePreCheck = 'candidate_pre_check';
    case ApplicationFormalCheck = 'application_formal_check';
    case BackofficeManualCheck = 'backoffice_manual_check';
    case SystemRecheck = 'system_recheck';

    public function label(): string
    {
        return match ($this) {
            self::CandidatePreCheck => 'Pré-verificação do candidato',
            self::ApplicationFormalCheck => 'Verificação formal da candidatura',
            self::BackofficeManualCheck => 'Verificação manual do backoffice',
            self::SystemRecheck => 'Reavaliação do sistema',
        };
    }
}
