<?php

namespace App\Enums\Dashboard\Timeline;

enum TimelineType: string
{
    case Task = 'task';
    case Visit = 'visit';
    case Inspection = 'inspection';
    case Deadline = 'deadline';
    case CorrectionRequest = 'correction-request';
    case CorrectionResponse = 'correction-response';
    case Hearing = 'hearing';
    case HearingSubmission = 'hearing-submission';
    case Complaint = 'complaint';
    case ComplaintAdditionalInformation = 'complaint-additional-information';
    case ComplaintDecision = 'complaint-decision';
    case MaintenanceRequest = 'maintenance-request';
    case MaintenanceIntervention = 'maintenance-intervention';
    case ApplicationSubmitted = 'application-submitted';
    case KeyHandover = 'key-handover';
    case RgpdRequest = 'rgpd-request';
    case InternalAlert = 'internal-alert';
    case AllocationOffer = 'allocation-offer';
    case AllocationAccepted = 'allocation-accepted';
    case AllocationReadyForContract = 'allocation-ready-for-contract';
    case LotteryScheduled = 'lottery-scheduled';
    case LotteryReady = 'lottery-ready';
    case LotteryCompleted = 'lottery-completed';
    case LotteryValidated = 'lottery-validated';
    case RentDue = 'rent-due';
    case RentOverdue = 'rent-overdue';
    case LeasePaymentReceived = 'lease-payment-received';
    case DocumentSubmitted = 'document-submitted';
    case DocumentUnderReview = 'document-under-review';
    case DocumentDossierIncomplete = 'document-dossier-incomplete';
    case AdditionalDocumentRequested = 'additional-document-requested';
    case AdditionalDocumentSubmitted = 'additional-document-submitted';
    case ContractIssued = 'contract-issued';
    case ContractSigned = 'contract-signed';
    case ContractActive = 'contract-active';
    case ContractSuspended = 'contract-suspended';
    case ContractTerminated = 'contract-terminated';
    case TenantTransitionPending = 'tenant-transition-pending';
    case TenantTransitionCompleted = 'tenant-transition-completed';
    case TenantInvoiceDue = 'tenant-invoice-due';
    case TenantInvoiceOverdue = 'tenant-invoice-overdue';
    case TenantPaymentRegistered = 'tenant-payment-registered';
    case TenantPaymentConfirmed = 'tenant-payment-confirmed';
    case TenantCommunicationOpen = 'tenant-communication-open';
    case TenantCommunicationAwaitingMunicipality = 'tenant-communication-awaiting-municipality';
}
