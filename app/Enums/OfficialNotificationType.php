<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum OfficialNotificationType: string
{
    use HasOptions;

    case ProvisionalListPublished = 'provisional_list_published';
    case ComplaintReceived = 'complaint_received';
    case AdditionalInformationRequested = 'additional_information_requested';
    case ComplaintDecided = 'complaint_decided';
    case HearingIssued = 'hearing_issued';
    case HearingSubmissionReceived = 'hearing_submission_received';
    case DefinitiveListPublished = 'definitive_list_published';
    case AllocationOfferIssued = 'allocation_offer_issued';
    case AllocationOfferAccepted = 'allocation_offer_accepted';
    case AllocationOfferRefused = 'allocation_offer_refused';
    case AllocationOfferExpired = 'allocation_offer_expired';
    case ReserveCandidateCalled = 'reserve_candidate_called';
    case AllocationReadyForContract = 'allocation_ready_for_contract';
    case ContractPreparationStarted = 'contract_preparation_started';
    case ContractIssued = 'contract_issued';
    case ContractSigned = 'contract_signed';
    case ContractActive = 'contract_active';
    case DepositRequested = 'deposit_requested';
    case DepositPaidRegistered = 'deposit_paid_registered';
    case RentScheduleGenerated = 'rent_schedule_generated';
    case RentInstallmentIssued = 'rent_installment_issued';
    case LeasePaymentRegistered = 'lease_payment_registered';
    case PaymentReceiptIssued = 'payment_receipt_issued';
    case ArrearDetected = 'arrear_detected';
    case DefaultNoticeIssued = 'default_notice_issued';
    case RegularizationAgreementCreated = 'regularization_agreement_created';
    case RentReviewRequested = 'rent_review_requested';
    case RentReviewApplied = 'rent_review_applied';
    case IncomeChangeSubmitted = 'income_change_submitted';
    case AnnualDocumentUpdateRequested = 'annual_document_update_requested';
    case MaintenanceRequestCreated = 'maintenance_request_created';
    case MaintenanceRequestUnderReview = 'maintenance_request_under_review';
    case MaintenanceRequestScheduled = 'maintenance_request_scheduled';
    case MaintenanceRequestInProgress = 'maintenance_request_in_progress';
    case MaintenanceRequestResolved = 'maintenance_request_resolved';
    case MaintenanceRequestRejected = 'maintenance_request_rejected';
    case MaintenanceRequestClosed = 'maintenance_request_closed';
    case InspectionScheduled = 'inspection_scheduled';
    case InspectionCompleted = 'inspection_completed';
    case InspectionReportAvailable = 'inspection_report_available';
    case VisitScheduled = 'visit_scheduled';
    case VisitConfirmed = 'visit_confirmed';
    case VisitRescheduled = 'visit_rescheduled';
    case VisitCancelled = 'visit_cancelled';
    case VisitCompleted = 'visit_completed';
    case VisitNoShow = 'visit_no_show';
    case SupportTicketCreated = 'support_ticket_created';
    case SupportTicketReply = 'support_ticket_reply';
    case SupportTicketResolved = 'support_ticket_resolved';
    case SupportTicketReopened = 'support_ticket_reopened';
    case ApplicationInconsistencyDetected = 'application_inconsistency_detected';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ProvisionalListPublished => 'Lista provisória publicada',
            self::ComplaintReceived => 'Reclamação recebida',
            self::AdditionalInformationRequested => 'Informação complementar solicitada',
            self::ComplaintDecided => 'Reclamação decidida',
            self::HearingIssued => 'Audiência emitida',
            self::HearingSubmissionReceived => 'Pronúncia recebida',
            self::DefinitiveListPublished => 'Lista definitiva publicada',
            self::AllocationOfferIssued => 'Oferta de atribuição emitida',
            self::AllocationOfferAccepted => 'Oferta de atribuição aceite',
            self::AllocationOfferRefused => 'Oferta de atribuição recusada',
            self::AllocationOfferExpired => 'Oferta de atribuição expirada',
            self::ReserveCandidateCalled => 'Suplente chamado',
            self::AllocationReadyForContract => 'Atribuição pronta para contrato',
            self::ContractPreparationStarted => 'Contrato em preparação',
            self::ContractIssued => 'Contrato emitido',
            self::ContractSigned => 'Contrato assinado',
            self::ContractActive => 'Contrato ativo',
            self::DepositRequested => 'Caução solicitada',
            self::DepositPaidRegistered => 'Caução registada como paga',
            self::RentScheduleGenerated => 'Plano de rendas gerado',
            self::RentInstallmentIssued => 'Prestação de renda emitida',
            self::LeasePaymentRegistered => 'Pagamento registado',
            self::PaymentReceiptIssued => 'Comprovativo interno emitido',
            self::ArrearDetected => 'Incumprimento detetado',
            self::DefaultNoticeIssued => 'Aviso de incumprimento emitido',
            self::RegularizationAgreementCreated => 'Acordo de regularização criado',
            self::RentReviewRequested => 'Revisão de renda solicitada',
            self::RentReviewApplied => 'Revisão de renda aplicada',
            self::IncomeChangeSubmitted => 'Alteração de rendimentos submetida',
            self::AnnualDocumentUpdateRequested => 'Atualização documental anual solicitada',
            self::MaintenanceRequestCreated => 'Pedido de manutenção criado',
            self::MaintenanceRequestUnderReview => 'Pedido de manutenção em análise',
            self::MaintenanceRequestScheduled => 'Pedido de manutenção agendado',
            self::MaintenanceRequestInProgress => 'Pedido de manutenção em execução',
            self::MaintenanceRequestResolved => 'Pedido de manutenção resolvido',
            self::MaintenanceRequestRejected => 'Pedido de manutenção rejeitado',
            self::MaintenanceRequestClosed => 'Pedido de manutenção fechado',
            self::InspectionScheduled => 'Vistoria agendada',
            self::InspectionCompleted => 'Vistoria concluída',
            self::InspectionReportAvailable => 'Auto de vistoria disponível',
            self::VisitScheduled => 'Visita solicitada',
            self::VisitConfirmed => 'Visita confirmada',
            self::VisitRescheduled => 'Visita reagendada',
            self::VisitCancelled => 'Visita cancelada',
            self::VisitCompleted => 'Visita concluída',
            self::VisitNoShow => 'Falta de comparência em visita',
            self::SupportTicketCreated => 'Pedido de apoio criado',
            self::SupportTicketReply => 'Resposta ao pedido de apoio',
            self::SupportTicketResolved => 'Pedido de apoio resolvido',
            self::SupportTicketReopened => 'Pedido de apoio reaberto',
            self::ApplicationInconsistencyDetected => 'Inconsistência de candidatura detetada',
            self::Other => 'Outra',
        };
    }
}
