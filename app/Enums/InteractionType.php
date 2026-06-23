<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InteractionType: string
{
    use HasOptions;

    case VisitScheduled = 'visit_scheduled';
    case VisitRescheduled = 'visit_rescheduled';
    case VisitCancelled = 'visit_cancelled';
    case VisitCompleted = 'visit_completed';
    case TicketCreated = 'ticket_created';
    case TicketMessageSent = 'ticket_message_sent';
    case TicketResolved = 'ticket_resolved';
    case FaqViewed = 'faq_viewed';
    case InconsistencyDetected = 'inconsistency_detected';
    case NotificationSent = 'notification_sent';

    public function label(): string
    {
        return match ($this) {
            self::VisitScheduled => 'Visita agendada',
            self::VisitRescheduled => 'Visita reagendada',
            self::VisitCancelled => 'Visita cancelada',
            self::VisitCompleted => 'Visita concluída',
            self::TicketCreated => 'Ticket criado',
            self::TicketMessageSent => 'Mensagem enviada',
            self::TicketResolved => 'Ticket resolvido',
            self::FaqViewed => 'FAQ consultada',
            self::InconsistencyDetected => 'Inconsistência detetada',
            self::NotificationSent => 'Notificação registada',
        };
    }
}
