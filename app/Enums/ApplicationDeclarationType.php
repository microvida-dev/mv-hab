<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationDeclarationType: string
{
    use HasOptions;

    case HonourDeclaration = 'honour_declaration';
    case ContestRulesAcceptance = 'contest_rules_acceptance';
    case DataProcessing = 'data_processing';
    case Truthfulness = 'truthfulness';
    case DataCurrent = 'data_current';

    public function label(): string
    {
        return match ($this) {
            self::HonourDeclaration => 'Declaração sob compromisso de honra',
            self::ContestRulesAcceptance => 'Aceitação das regras do concurso',
            self::DataProcessing => 'Tratamento de dados da candidatura',
            self::Truthfulness => 'Confirmação de veracidade',
            self::DataCurrent => 'Confirmação de dados atuais',
        };
    }
}
