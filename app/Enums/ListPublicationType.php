<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ListPublicationType: string
{
    use HasOptions;

    case ProvisionalList = 'provisional_list';
    case DefinitiveList = 'definitive_list';
    case HearingNotice = 'hearing_notice';
    case ComplaintDecisionNotice = 'complaint_decision_notice';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ProvisionalList => 'Lista provisória',
            self::DefinitiveList => 'Lista definitiva',
            self::HearingNotice => 'Aviso de audiência',
            self::ComplaintDecisionNotice => 'Aviso de decisão de reclamação',
            self::Other => 'Outro',
        };
    }
}
