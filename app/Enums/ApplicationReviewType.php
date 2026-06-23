<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationReviewType: string
{
    use HasOptions;

    case Preliminary = 'preliminary';
    case Documental = 'documental';
    case Eligibility = 'eligibility';
    case CorrectionResponse = 'correction_response';
    case Admission = 'admission';

    public function label(): string
    {
        return match ($this) {
            self::Preliminary => 'Triagem inicial',
            self::Documental => 'Análise documental',
            self::Eligibility => 'Análise de requisitos',
            self::CorrectionResponse => 'Resposta a aperfeiçoamento',
            self::Admission => 'Admissão',
        };
    }
}
