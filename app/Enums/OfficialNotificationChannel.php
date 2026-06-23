<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum OfficialNotificationChannel: string
{
    use HasOptions;

    case InApp = 'in_app';
    case Internal = 'internal';
    case CandidateArea = 'candidate_area';
    case Backoffice = 'backoffice';
    case Email = 'email';
    case Sms = 'sms';
    case Postal = 'postal';
    case Document = 'document';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::InApp => 'Área pessoal',
            self::Internal => 'Backoffice',
            self::CandidateArea => 'Área do candidato',
            self::Backoffice => 'Backoffice',
            self::Email => 'Email',
            self::Sms => 'SMS',
            self::Postal => 'Postal',
            self::Document => 'Documento',
            self::Other => 'Outro',
        };
    }
}
