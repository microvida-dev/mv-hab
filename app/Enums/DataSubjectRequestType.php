<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DataSubjectRequestType: string
{
    use HasOptions;

    case Access = 'access';
    case Rectification = 'rectification';
    case Erasure = 'erasure';
    case Restriction = 'restriction';
    case Portability = 'portability';
    case Objection = 'objection';
    case WithdrawConsent = 'withdraw_consent';
    case Information = 'information';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Access => 'Acesso aos dados',
            self::Rectification => 'Retificação',
            self::Erasure => 'Apagamento',
            self::Restriction => 'Limitação do tratamento',
            self::Portability => 'Portabilidade',
            self::Objection => 'Oposição',
            self::WithdrawConsent => 'Retirada de consentimento',
            self::Information => 'Pedido de informação',
            self::Other => 'Outro',
        };
    }
}
