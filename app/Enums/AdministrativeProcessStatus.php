<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AdministrativeProcessStatus: string
{
    use HasOptions;

    case Submitted = 'submitted';
    case Received = 'received';
    case Assigned = 'assigned';
    case PreliminaryReview = 'preliminary_review';
    case DocumentReview = 'document_review';
    case EligibilityReview = 'eligibility_review';
    case RequiresCorrection = 'requires_correction';
    case AwaitingCandidateResponse = 'awaiting_candidate_response';
    case CorrectionSubmitted = 'correction_submitted';
    case CorrectionOverdue = 'correction_overdue';
    case CorrectionUnderReview = 'correction_under_review';
    case AdmittedForScoring = 'admitted_for_scoring';
    case NotAdmitted = 'not_admitted';
    case Withdrawn = 'withdrawn';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submetido',
            self::Received => 'Recebido',
            self::Assigned => 'Atribuído',
            self::PreliminaryReview => 'Triagem inicial',
            self::DocumentReview => 'Análise documental',
            self::EligibilityReview => 'Análise de requisitos',
            self::RequiresCorrection => 'Requer aperfeiçoamento',
            self::AwaitingCandidateResponse => 'Aguarda resposta do candidato',
            self::CorrectionSubmitted => 'Aperfeiçoamento submetido',
            self::CorrectionOverdue => 'Aperfeiçoamento vencido',
            self::CorrectionUnderReview => 'Resposta em análise',
            self::AdmittedForScoring => 'Admitido para classificação',
            self::NotAdmitted => 'Não admitido',
            self::Withdrawn => 'Desistido',
            self::Cancelled => 'Cancelado',
            self::Archived => 'Arquivado',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::AdmittedForScoring,
            self::NotAdmitted,
            self::Withdrawn,
            self::Cancelled,
            self::Archived,
        ], true);
    }
}
