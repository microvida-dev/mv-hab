<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PublicProcessStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case RegistrationRequired = 'registration_required';
    case SimulationRequired = 'simulation_required';
    case ReadyToApply = 'ready_to_apply';
    case Submitted = 'submitted';
    case Received = 'received';
    case UnderReview = 'under_review';
    case AwaitingDocuments = 'awaiting_documents';
    case AwaitingCorrection = 'awaiting_correction';
    case CorrectionSubmitted = 'correction_submitted';
    case AwaitingPreliminaryHearing = 'awaiting_preliminary_hearing';
    case PreliminaryHearingSubmitted = 'preliminary_hearing_submitted';
    case Admitted = 'admitted';
    case NotAdmitted = 'not_admitted';
    case Scoring = 'scoring';
    case Ranked = 'ranked';
    case ProvisionalListPublished = 'provisional_list_published';
    case ComplaintPeriod = 'complaint_period';
    case ComplaintSubmitted = 'complaint_submitted';
    case ComplaintUnderReview = 'complaint_under_review';
    case DefinitiveListPublished = 'definitive_list_published';
    case Allocated = 'allocated';
    case NotAllocated = 'not_allocated';
    case ContractPending = 'contract_pending';
    case Completed = 'completed';
    case Withdrawn = 'withdrawn';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Candidatura em preparação',
            self::RegistrationRequired => 'Registo de adesão necessário',
            self::SimulationRequired => 'Simulação recomendada',
            self::ReadyToApply => 'Pronta para candidatura',
            self::Submitted => 'Candidatura submetida',
            self::Received => 'Recebida pelos serviços municipais',
            self::UnderReview => 'Em análise pelos serviços municipais',
            self::AwaitingDocuments => 'A aguardar documentos',
            self::AwaitingCorrection => 'A aguardar resposta do candidato',
            self::CorrectionSubmitted => 'Resposta recebida',
            self::AwaitingPreliminaryHearing => 'Em audiência prévia',
            self::PreliminaryHearingSubmitted => 'Pronúncia submetida',
            self::Admitted => 'Admitida para classificação',
            self::NotAdmitted => 'Não admitida',
            self::Scoring => 'Em fase de classificação',
            self::Ranked => 'Ordenada em ranking',
            self::ProvisionalListPublished => 'Lista provisória publicada',
            self::ComplaintPeriod => 'Período de reclamação aberto',
            self::ComplaintSubmitted => 'Reclamação submetida',
            self::ComplaintUnderReview => 'Reclamação em análise',
            self::DefinitiveListPublished => 'Lista definitiva publicada',
            self::Allocated => 'Habitação atribuída',
            self::NotAllocated => 'Sem atribuição',
            self::ContractPending => 'Contrato em preparação',
            self::Completed => 'Processo concluído',
            self::Withdrawn => 'Candidatura desistida',
            self::Cancelled => 'Processo cancelado',
            self::Archived => 'Processo arquivado',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Withdrawn, self::Cancelled, self::Archived], true);
    }
}
