<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ListAutomationStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Generated = 'generated';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Published = 'published';
    case Archived = 'archived';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Generated => 'Gerada',
            self::UnderReview => 'Em revisão',
            self::Approved => 'Aprovada',
            self::Published => 'Publicada',
            self::Archived => 'Arquivada',
            self::Failed => 'Falhou',
        };
    }
}
