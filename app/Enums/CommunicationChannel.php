<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CommunicationChannel: string
{
    use HasOptions;

    case InApp = 'in_app';
    case Internal = 'internal';
    case Email = 'email';
    case Sms = 'sms';
    case Postal = 'postal';
    case Document = 'document';

    public function label(): string
    {
        return match ($this) {
            self::InApp => 'Área pessoal',
            self::Internal => 'Backoffice',
            self::Email => 'Email',
            self::Sms => 'SMS',
            self::Postal => 'Postal',
            self::Document => 'Documento',
        };
    }
}
