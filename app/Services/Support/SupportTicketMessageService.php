<?php

namespace App\Services\Support;

use App\Enums\InteractionType;
use App\Enums\MessageVisibility;
use App\Enums\OfficialNotificationType;
use App\Enums\TicketStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\CandidateExperience\CandidateInteractionService;
use App\Services\Notifications\OfficialNotificationService;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SupportTicketMessageService
{
    public function __construct(
        private readonly CandidateInteractionService $interactions,
        private readonly AuditLogger $auditLogger,
        private readonly OfficialNotificationService $notifications,
    ) {}

    public function addMessage(SupportTicket $ticket, User $sender, string $message, MessageVisibility $visibility): SupportTicketMessage
    {
        return DB::transaction(function () use ($ticket, $sender, $message, $visibility): SupportTicketMessage {
            $ticket = SupportTicket::query()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();

            if ($sender->hasRole('candidate') && ! $ticket->acceptsCandidateReply()) {
                throw ValidationException::withMessages(['message' => 'Este pedido já não aceita novas respostas do candidato.']);
            }

            if ($sender->hasRole('candidate') && $visibility !== MessageVisibility::CandidateVisible) {
                throw ValidationException::withMessages(['visibility' => 'O candidato não pode criar mensagens internas.']);
            }

            $entry = new SupportTicketMessage([
                'message' => trim(strip_tags($message)),
                'visibility' => $visibility,
            ]);
            $entry->forceFill([
                'support_ticket_id' => $ticket->id,
                'sender_user_id' => $sender->id,
            ])->save();

            $ticket->forceFill([
                'status' => $sender->hasRole('candidate') ? TicketStatus::PendingStaff : TicketStatus::PendingCandidate,
                'last_message_at' => now(),
            ])->save();

            $candidate = User::query()->findOrFail($ticket->user_id);

            if ($visibility !== MessageVisibility::InternalOnly) {
                $this->interactions->record(
                    user: $candidate,
                    type: InteractionType::TicketMessageSent,
                    title: 'Nova mensagem no pedido de apoio',
                    related: $ticket,
                    application: $ticket->application,
                    contest: $ticket->contest,
                    housingUnit: $ticket->housingUnit,
                    actor: $sender,
                );
            }

            $this->auditLogger->record(AuditEvents::CREATE, $entry, 'support', 'support_ticket_message', 'Mensagem de ticket criada.', metadata: [
                'ticket_id' => $ticket->id,
                'visibility' => $visibility->value,
            ]);

            if (! $sender->hasRole('candidate') && $visibility !== MessageVisibility::InternalOnly) {
                $this->notifyCandidate($ticket);
            }

            return $entry->refresh();
        });
    }

    public function markReadByCandidate(SupportTicketMessage $message): SupportTicketMessage
    {
        $message->forceFill(['read_by_candidate_at' => now()])->save();

        return $message->refresh();
    }

    public function markReadByStaff(SupportTicketMessage $message): SupportTicketMessage
    {
        $message->forceFill(['read_by_staff_at' => now()])->save();

        return $message->refresh();
    }

    private function notifyCandidate(SupportTicket $ticket): void
    {
        $candidate = User::query()->findOrFail($ticket->user_id);

        try {
            $this->notifications->createInternal(
                user: $candidate,
                type: OfficialNotificationType::SupportTicketReply,
                subject: 'Nova resposta ao pedido de apoio',
                body: 'Existe uma nova resposta no seu pedido de apoio.',
                notifiable: $ticket,
                application: $ticket->application,
                actionUrl: route('candidate.support-tickets.show', $ticket, false),
            );
        } catch (Throwable) {
            // A conversa interna não deve falhar por indisponibilidade de comunicações.
        }
    }
}
