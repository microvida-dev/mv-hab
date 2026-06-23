<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TimelineEventType: string
{
    use HasOptions;

    case ApplicationCreated = 'application_created';
    case ApplicationSubmitted = 'application_submitted';
    case StatusChanged = 'status_changed';
    case DocumentUploaded = 'document_uploaded';
    case DocumentValidated = 'document_validated';
    case DocumentRejected = 'document_rejected';
    case AdditionalDocumentRequested = 'additional_document_requested';
    case AdditionalDocumentSubmitted = 'additional_document_submitted';
    case CorrectionRequested = 'correction_requested';
    case CorrectionSubmitted = 'correction_submitted';
    case NotificationSent = 'notification_sent';
    case NotificationRead = 'notification_read';
    case PreliminaryHearingOpened = 'preliminary_hearing_opened';
    case PreliminaryHearingSubmitted = 'preliminary_hearing_submitted';
    case ComplaintSubmitted = 'complaint_submitted';
    case ComplaintDecided = 'complaint_decided';
    case VisitScheduled = 'visit_scheduled';
    case VisitCompleted = 'visit_completed';
    case TicketCreated = 'ticket_created';
    case TicketResolved = 'ticket_resolved';
    case InconsistencyDetected = 'inconsistency_detected';
    case WithdrawalRequested = 'withdrawal_requested';
    case ApplicationWithdrawn = 'application_withdrawn';
    case DataReused = 'data_reused';
    case ApplicationPrefilled = 'application_prefilled';
    case ManualNote = 'manual_note';
    case SystemEvent = 'system_event';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->headline()->toString();
    }
}
