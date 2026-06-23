<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentAiExtractionStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case ManualReview = 'manual_review';
    case UnsupportedDocumentType = 'unsupported_document_type';
    case LowConfidence = 'low_confidence';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Processing => 'Em processamento',
            self::Completed => 'Concluída',
            self::Failed => 'Falhada',
            self::ManualReview => 'Revisão manual',
            self::UnsupportedDocumentType => 'Tipo não suportado',
            self::LowConfidence => 'Baixa confiança',
        };
    }
}
