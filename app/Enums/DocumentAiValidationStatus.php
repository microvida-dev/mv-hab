<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentAiValidationStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Match = 'match';
    case PartialMatch = 'partial_match';
    case Mismatch = 'mismatch';
    case Inconclusive = 'inconclusive';
    case NotApplicable = 'not_applicable';
    case MissingCandidateValue = 'missing_candidate_value';
    case MissingDocumentValue = 'missing_document_value';
    case ManualReview = 'manual_review';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Processing => 'Em processamento',
            self::Completed => 'Concluída',
            self::Match => 'Coincide',
            self::PartialMatch => 'Parcialmente compatível',
            self::Mismatch => 'Divergente',
            self::Inconclusive => 'Inconclusivo',
            self::NotApplicable => 'Não aplicável',
            self::MissingCandidateValue => 'Valor declarado em falta',
            self::MissingDocumentValue => 'Valor documental em falta',
            self::ManualReview => 'Revisão manual',
            self::Failed => 'Falhou',
        };
    }
}
