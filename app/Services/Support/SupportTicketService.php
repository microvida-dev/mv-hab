<?php

namespace App\Services\Support;

use App\Enums\InteractionType;
use App\Enums\MessageVisibility;
use App\Enums\OfficialNotificationType;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Application;
use App\Models\Contest;
use App\Models\HousingUnit;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\CandidateExperience\CandidateInteractionService;
use App\Services\Notifications\OfficialNotificationService;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SupportTicketService
{
    public function __construct(
        private readonly CandidateInteractionService $interactions,
        private readonly AuditLogger $auditLogger,
        private readonly OfficialNotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $candidate, array $data): SupportTicket
    {
        return DB::transaction(function () use ($candidate, $data): SupportTicket {
            $application = $this->applicationForCandidate($candidate, $data);
            $contest = $this->contestFromData($data, $application);
            $housingUnit = $this->housingUnitFromData($data);

            $ticket = new SupportTicket([
                'subject' => trim((string) $data['subject']),
                'description' => $this->plainText((string) $data['description']),
            ]);
            $ticket->forceFill([
                'ticket_number' => $this->nextNumber(),
                'user_id' => $candidate->id,
                'application_id' => $application?->id,
                'contest_id' => $contest?->id,
                'housing_unit_id' => $housingUnit?->id,
                'category' => TicketCategory::from((string) $data['category']),
                'priority' => TicketPriority::tryFrom((string) ($data['priority'] ?? '')) ?? TicketPriority::Normal,
                'status' => TicketStatus::Open,
                'context' => $this->context($data['context'] ?? null),
                'last_message_at' => now(),
            ])->save();

            $message = new SupportTicketMessage([
                'message' => $ticket->description,
                'visibility' => MessageVisibility::CandidateVisible,
            ]);
            $message->forceFill([
                'support_ticket_id' => $ticket->id,
                'sender_user_id' => $candidate->id,
            ])->save();

            $this->interactions->record(
                user: $candidate,
                type: InteractionType::TicketCreated,
                title: 'Pedido de apoio criado',
                description: 'O pedido de apoio foi registado na plataforma.',
                related: $ticket,
                application: $application,
                contest: $contest,
                housingUnit: $housingUnit,
                actor: $candidate,
            );

            $this->auditLogger->record(AuditEvents::CREATE, $ticket, 'support', 'support_ticket_create', 'Ticket de apoio criado.');
            $this->notify($candidate, $ticket, OfficialNotificationType::SupportTicketCreated, 'Pedido de apoio criado', 'O seu pedido de apoio foi registado.');

            return $ticket->refresh();
        });
    }

    public function assign(SupportTicket $ticket, User $staff, User $actor): SupportTicket
    {
        $ticket->forceFill([
            'assigned_to' => $staff->id,
            'status' => TicketStatus::InProgress,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $ticket, 'support', 'support_ticket_assign', 'Ticket atribuído a técnico.', metadata: [
            'assigned_to' => $staff->id,
            'actor_id' => $actor->id,
        ]);

        return $ticket->refresh();
    }

    public function updateStatus(SupportTicket $ticket, TicketStatus $status, User $actor, ?string $message = null): SupportTicket
    {
        $payload = ['status' => $status];

        if ($status === TicketStatus::Resolved) {
            $payload['resolved_at'] = now();
        }

        if ($status === TicketStatus::Closed) {
            $payload['closed_at'] = now();
        }

        if ($status === TicketStatus::Reopened) {
            $payload['resolved_at'] = null;
            $payload['closed_at'] = null;
        }

        $ticket->forceFill($payload)->save();
        $candidate = User::query()->findOrFail($ticket->user_id);

        if ($message !== null && trim($message) !== '') {
            $systemMessage = new SupportTicketMessage([
                'message' => $this->plainText($message),
                'visibility' => MessageVisibility::System,
            ]);
            $systemMessage->forceFill(['support_ticket_id' => $ticket->id])->save();
        }

        if (in_array($status, [TicketStatus::Resolved, TicketStatus::Reopened], true)) {
            $this->interactions->record(
                user: $candidate,
                type: $status === TicketStatus::Resolved ? InteractionType::TicketResolved : InteractionType::TicketMessageSent,
                title: $status === TicketStatus::Resolved ? 'Pedido de apoio resolvido' : 'Pedido de apoio reaberto',
                related: $ticket,
                application: $ticket->application,
                contest: $ticket->contest,
                housingUnit: $ticket->housingUnit,
                actor: $actor,
            );
        }

        $this->auditLogger->record(AuditEvents::UPDATE, $ticket, 'support', 'support_ticket_status', 'Estado do ticket atualizado.', metadata: [
            'status' => $status->value,
            'actor_id' => $actor->id,
        ]);

        if ($status === TicketStatus::Resolved) {
            $this->notify($candidate, $ticket, OfficialNotificationType::SupportTicketResolved, 'Pedido de apoio resolvido', 'O seu pedido de apoio foi marcado como resolvido.');
        }

        return $ticket->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applicationForCandidate(User $candidate, array $data): ?Application
    {
        if (empty($data['application_id'])) {
            return null;
        }

        $application = Application::query()->findOrFail((int) $data['application_id']);
        if ($application->user_id !== $candidate->id) {
            throw ValidationException::withMessages(['application_id' => 'A candidatura indicada não pertence ao candidato autenticado.']);
        }

        return $application;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function contestFromData(array $data, ?Application $application): ?Contest
    {
        $contestId = $application?->contest_id ?: ($data['contest_id'] ?? null);

        return $contestId ? Contest::query()->find((int) $contestId) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function housingUnitFromData(array $data): ?HousingUnit
    {
        return ! empty($data['housing_unit_id']) ? HousingUnit::query()->find((int) $data['housing_unit_id']) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function context(mixed $context): ?array
    {
        return is_array($context) ? $context : null;
    }

    private function nextNumber(): string
    {
        $next = (int) SupportTicket::query()->withTrashed()->max('id') + 1;

        return 'SUP-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function plainText(string $value): string
    {
        return trim(strip_tags($value));
    }

    private function notify(User $user, Model $related, OfficialNotificationType $type, string $subject, string $body): void
    {
        try {
            $this->notifications->createInternal($user, $type, $subject, $body, $related);
        } catch (Throwable) {
            // O apoio não depende de canal externo ou de configuração de comunicações.
        }
    }
}
