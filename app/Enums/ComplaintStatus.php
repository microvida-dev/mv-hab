<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ComplaintStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case Received = 'received';
    case UnderReview = 'under_review';
    case RequiresAdditionalInformation = 'requires_additional_information';
    case AwaitingCandidateResponse = 'awaiting_candidate_response';
    case AdditionalInformationSubmitted = 'additional_information_submitted';
    case Accepted = 'accepted';
    case PartiallyAccepted = 'partially_accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Cancelled = 'cancelled';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Submitted => 'Submetida',
            self::Received => 'Recebida',
            self::UnderReview => 'Em análise',
            self::RequiresAdditionalInformation => 'Requer informação complementar',
            self::AwaitingCandidateResponse => 'A aguardar resposta do candidato',
            self::AdditionalInformationSubmitted => 'Informação complementar submetida',
            self::Accepted => 'Aceite',
            self::PartiallyAccepted => 'Parcialmente aceite',
            self::Rejected => 'Indeferida',
            self::Withdrawn => 'Desistida',
            self::Cancelled => 'Cancelada',
            self::Closed => 'Fechada',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::Accepted,
            self::PartiallyAccepted,
            self::Rejected,
            self::Withdrawn,
            self::Cancelled,
            self::Closed,
        ], true);
    }
}
