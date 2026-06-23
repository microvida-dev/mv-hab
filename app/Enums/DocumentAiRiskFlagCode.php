<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentAiRiskFlagCode: string
{
    use HasOptions;

    case DocumentExpired = 'document_expired';
    case DocumentUnreadable = 'document_unreadable';
    case PageCropped = 'page_cropped';
    case InsufficientOcr = 'insufficient_ocr';
    case NifMismatch = 'nif_mismatch';
    case NameMismatch = 'name_mismatch';
    case IncomeIncompatible = 'income_incompatible';
    case DuplicateDocument = 'duplicate_document';
    case EmptyDocument = 'empty_document';
    case MissingRequiredFields = 'missing_required_fields';

    public function label(): string
    {
        return match ($this) {
            self::DocumentExpired => 'Documento expirado',
            self::DocumentUnreadable => 'Documento ilegível',
            self::PageCropped => 'Página cortada',
            self::InsufficientOcr => 'OCR insuficiente',
            self::NifMismatch => 'NIF diferente',
            self::NameMismatch => 'Nome diferente',
            self::IncomeIncompatible => 'Rendimento incompatível',
            self::DuplicateDocument => 'Documento duplicado',
            self::EmptyDocument => 'Documento vazio',
            self::MissingRequiredFields => 'Campos obrigatórios ausentes',
        };
    }
}
