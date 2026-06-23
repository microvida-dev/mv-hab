<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InternalAlertType: string
{
    use HasOptions;

    case DeadlineApproaching = 'deadline_approaching';
    case DeadlineExpired = 'deadline_expired';
    case DocumentsPending = 'documents_pending';
    case DocumentsRejected = 'documents_rejected';
    case ApplicationUnassigned = 'application_unassigned';
    case VisitPending = 'visit_pending';
    case TicketPending = 'ticket_pending';
    case ListGenerationPending = 'list_generation_pending';
    case MinuteReviewPending = 'minute_review_pending';
    case ReportFailed = 'report_failed';
    case ProcessConfirmationPending = 'process_confirmation_pending';

    public function label(): string
    {
        return match ($this) {
            self::DeadlineApproaching => 'Prazo a expirar',
            self::DeadlineExpired => 'Prazo expirado',
            self::DocumentsPending => 'Documentos pendentes',
            self::DocumentsRejected => 'Documentos rejeitados',
            self::ApplicationUnassigned => 'Candidatura sem técnico',
            self::VisitPending => 'Visita pendente',
            self::TicketPending => 'Ticket pendente',
            self::ListGenerationPending => 'Lista por gerar',
            self::MinuteReviewPending => 'Ata por rever',
            self::ReportFailed => 'Relatório falhou',
            self::ProcessConfirmationPending => 'Confirmação de processo pendente',
        };
    }
}
