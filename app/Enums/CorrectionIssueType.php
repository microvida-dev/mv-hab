<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CorrectionIssueType: string
{
    use HasOptions;

    case MissingDocument = 'missing_document';
    case RejectedDocument = 'rejected_document';
    case ExpiredDocument = 'expired_document';
    case MissingData = 'missing_data';
    case InconsistentData = 'inconsistent_data';
    case UnclearInformation = 'unclear_information';
    case EligibilityIssue = 'eligibility_issue';
    case ManualReview = 'manual_review';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MissingDocument => 'Documento em falta',
            self::RejectedDocument => 'Documento rejeitado',
            self::ExpiredDocument => 'Documento expirado',
            self::MissingData => 'Dado em falta',
            self::InconsistentData => 'Dado inconsistente',
            self::UnclearInformation => 'Informação pouco clara',
            self::EligibilityIssue => 'Questão de requisito',
            self::ManualReview => 'Análise manual',
            self::Other => 'Outro',
        };
    }
}
