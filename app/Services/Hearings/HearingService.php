<?php

namespace App\Services\Hearings;

use App\Enums\HearingStatus;
use App\Enums\OfficialNotificationType;
use App\Models\Application;
use App\Models\Hearing;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\OfficialNotificationService;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HearingService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly OfficialNotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Hearing
    {
        $application = Application::query()->findOrFail($data['application_id']);

        $hearing = new Hearing($data);
        $hearing->forceFill([
            'application_id' => $application->id,
            'user_id' => $application->user_id,
            'hearing_number' => $this->generateHearingNumber(),
            'status' => HearingStatus::Draft,
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $hearing, 'complaints', 'hearing_create', 'Audiência de interessados criada.');

        return $hearing->refresh();
    }

    public function issue(Hearing $hearing, User $actor): Hearing
    {
        if ($this->hearingStatus($hearing) !== HearingStatus::Draft) {
            throw ValidationException::withMessages(['hearing' => 'Apenas audiências em rascunho podem ser emitidas.']);
        }

        $notificationService = $this->notificationService;

        return DB::transaction(function () use ($hearing, $actor, $notificationService) {
            $hearing->forceFill([
                'status' => HearingStatus::Open,
                'issued_by' => $actor->id,
                'issued_at' => now(),
                'candidate_visible' => true,
            ])->save();

            $notificationService->createInternal(
                user: $this->requiredCandidate($hearing),
                type: OfficialNotificationType::HearingIssued,
                subject: 'Audiência de interessados',
                body: 'Foi-lhe concedida audiência de interessados para se pronunciar sobre os elementos indicados. A sua pronúncia deve ser submetida dentro do prazo definido.',
                notifiable: $hearing,
                application: $this->optionalApplication($hearing),
                actor: $actor,
            );

            $this->auditLogger->record(AuditEvents::PUBLISH, $hearing, 'complaints', 'hearing_issue', 'Audiência de interessados emitida.');

            return $hearing->refresh();
        });
    }

    public function close(Hearing $hearing, User $actor): Hearing
    {
        $hearing->forceFill(['status' => HearingStatus::Closed, 'closed_at' => now()])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $hearing, 'complaints', 'hearing_close', 'Audiência de interessados fechada.');

        return $hearing->refresh();
    }

    public function cancel(Hearing $hearing, User $actor): Hearing
    {
        if ($this->hearingStatus($hearing) === HearingStatus::Completed) {
            throw ValidationException::withMessages(['hearing' => 'Audiências concluídas não podem ser canceladas.']);
        }

        $hearing->forceFill(['status' => HearingStatus::Cancelled, 'candidate_visible' => false])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $hearing, 'complaints', 'hearing_cancel', 'Audiência de interessados cancelada.');

        return $hearing->refresh();
    }

    private function generateHearingNumber(): string
    {
        $next = Hearing::withTrashed()->count() + 1;

        do {
            $number = 'AUD-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (Hearing::withTrashed()->where('hearing_number', $number)->exists());

        return $number;
    }

    private function hearingStatus(Hearing $hearing): ?HearingStatus
    {
        $status = $hearing->getAttribute('status');

        if ($status instanceof HearingStatus) {
            return $status;
        }

        return is_string($status) ? HearingStatus::tryFrom($status) : null;
    }

    private function requiredCandidate(Hearing $hearing): User
    {
        $candidate = $hearing->candidate;

        if (! $candidate instanceof User) {
            throw ValidationException::withMessages(['candidate' => 'A audiência não tem candidato associado.']);
        }

        return $candidate;
    }

    private function optionalApplication(Hearing $hearing): ?Application
    {
        $application = $hearing->application;

        return $application instanceof Application ? $application : null;
    }
}
