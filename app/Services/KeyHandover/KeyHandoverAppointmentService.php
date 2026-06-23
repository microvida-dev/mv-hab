<?php

namespace App\Services\KeyHandover;

use App\Enums\KeyHandoverStatus;
use App\Models\KeyHandoverAppointment;
use App\Models\User;
use App\Models\WinnerRegistration;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Validation\ValidationException;

class KeyHandoverAppointmentService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function schedule(WinnerRegistration $winner, array $data, User $actor): KeyHandoverAppointment
    {
        if ($winner->status !== 'active') {
            throw ValidationException::withMessages(['winner_registration_id' => 'A entrega de chaves exige vencedor ativo.']);
        }

        $appointment = new KeyHandoverAppointment([
            'winner_registration_id' => $winner->id,
            'allocation_id' => $winner->allocation_id,
            'application_id' => $winner->application_id,
            'user_id' => $winner->user_id,
            'contest_id' => $winner->lotteryDraw->contest_id,
            'contest_housing_unit_id' => $winner->contest_housing_unit_id,
            'housing_unit_id' => $winner->housing_unit_id,
            'scheduled_for' => $data['scheduled_for'],
            'location' => $data['location'],
            'instructions' => $data['instructions'] ?? 'A entrega de chaves só deve ocorrer após validação dos requisitos administrativos, contratuais e documentais aplicáveis.',
            'internal_notes' => $data['internal_notes'] ?? null,
        ]);

        $appointment->forceFill([
            'status' => KeyHandoverStatus::Scheduled,
        ])->save();

        $this->audit->record(AuditEvents::CREATE, $appointment, 'allocations', 'key_handover_schedule', 'Entrega de chaves agendada.');

        return $appointment->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(KeyHandoverAppointment $appointment, array $data, User $actor): KeyHandoverAppointment
    {
        $appointment->fill($data);
        $appointment->forceFill([
            'status' => KeyHandoverStatus::Rescheduled,
            'rescheduled_at' => now(),
        ])->save();

        $this->audit->record(AuditEvents::UPDATE, $appointment, 'allocations', 'key_handover_reschedule', 'Entrega de chaves reagendada.');

        return $appointment->refresh();
    }

    public function complete(KeyHandoverAppointment $appointment, User $actor, ?string $notes = null): KeyHandoverAppointment
    {
        $appointment->forceFill([
            'status' => KeyHandoverStatus::Completed,
            'completed_at' => now(),
            'completed_by' => $actor->id,
            'internal_notes' => $notes ?? $appointment->internal_notes,
        ])->save();

        $this->audit->record(AuditEvents::APPROVE, $appointment, 'allocations', 'key_handover_complete', 'Entrega de chaves concluída.');

        return $appointment->refresh();
    }

    public function cancel(KeyHandoverAppointment $appointment, User $actor, string $reason): KeyHandoverAppointment
    {
        $appointment->forceFill([
            'status' => KeyHandoverStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $actor->id,
            'cancellation_reason' => $reason,
        ])->save();

        $this->audit->record(AuditEvents::UPDATE, $appointment, 'allocations', 'key_handover_cancel', 'Entrega de chaves cancelada.');

        return $appointment->refresh();
    }
}
