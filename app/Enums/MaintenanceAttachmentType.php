<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MaintenanceAttachmentType: string
{
    use HasOptions;

    case Photo = 'photo';
    case Document = 'document';
    case Invoice = 'invoice';
    case TechnicalReport = 'technical_report';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Photo => 'Fotografia',
            self::Document => 'Documento',
            self::Invoice => 'Fatura',
            self::TechnicalReport => 'Relatório técnico',
            self::Other => 'Outro',
        };
    }
}
