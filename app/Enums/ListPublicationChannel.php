<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ListPublicationChannel: string
{
    use HasOptions;

    case PublicPortal = 'public_portal';
    case CandidateArea = 'candidate_area';
    case Backoffice = 'backoffice';
    case MunicipalWebsite = 'municipal_website';
    case NoticeBoard = 'notice_board';
    case Email = 'email';
    case Sms = 'sms';
    case Postal = 'postal';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PublicPortal => 'Portal público',
            self::CandidateArea => 'Área do candidato',
            self::Backoffice => 'Backoffice',
            self::MunicipalWebsite => 'Website municipal',
            self::NoticeBoard => 'Edital',
            self::Email => 'Email',
            self::Sms => 'SMS',
            self::Postal => 'Postal',
            self::Other => 'Outro',
        };
    }
}
