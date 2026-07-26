<?php

namespace App\Services\KeyHandover;

use App\Enums\KeyHandoverStatus;
use App\Models\KeyHandoverAppointment;
use App\Models\User;
use App\Models\WinnerRegistration;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KeyHandoverAppointmentService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function schedule(WinnerRegistration $winner, array $data, User $actor): KeyHandoverAppointment
    {
        return DB::transaction(function () use ($winner, $data, $actor): KeyHandoverAppointment {
            /** @var WinnerRegistration $lockedWinner */
            $lockedWinner = WinnerRegistration::query()
                ->lockForUpdate()
                ->with('lotteryDraw')
                ->findOrFail($winner->getKey());

            if ($lockedWinner->status !== 'active') {
                throw ValidationException::withMessages([
                    'winner_registration_id' => 'A entrega de chaves exige vencedor ativo.',
                ]);
            }

            $existing = $lockedWinner->keyHandoverAppointments()
                ->whereIn('status', [
                    KeyHandoverStatus::Scheduled->value,
                    KeyHandoverStatus::Rescheduled->value,
                    KeyHandoverStatus::Completed->value,
                ])
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($existing instanceof KeyHandoverAppointment) {
                if (
                    $existing->status !== KeyHandoverStatus::Completed
                    && $existing->scheduled_for?->equalTo(Carbon::parse($data['scheduled_for']))
                    && $existing->location === $data['location']
                ) {
                    return $existing;
                }

                throw ValidationException::withMessages([
                    'winner_registration_id' => 'Já existe uma entrega de chaves ativa ou concluída para este vencedor.',
                ]);
            }

            $appointment = new KeyHandoverAppointment([
                'winner_registration_id' => $lockedWinner->id,
                'allocation_id' => $lockedWinner->allocation_id,
                'application_id' => $lockedWinner->application_id,
                'user_id' => $lockedWinner->user_id,
                'contest_id' => $lockedWinner->lotteryDraw->contest_id,
                'contest_housing_unit_id' => $lockedWinner->contest_housing_unit_id,
                'housing_unit_id' => $lockedWinner->housing_unit_id,
                'scheduled_for' => $data['scheduled_for'],
                'location' => $data['location'],
                'instructions' => $data['instructions'] ?? 'A entrega de chaves só deve ocorrer após validação dos requisitos administrativos, contratuais e documentais aplicáveis.',
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);

            $appointment->forceFill([
                'status' => KeyHandoverStatus::Scheduled,
            ])->save();

            $this->audit->record(
                AuditEvents::CREATE,
                $appointment,
                'allocations',
                'key_handover_schedule',
                'Entrega de chaves agendada.',
                metadata: ['actor_id' => $actor->id],
            );

            return $appointment->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(KeyHandoverAppointment $appointment, array $data, User $actor): KeyHandoverAppointment
    {
        return DB::transaction(function () use ($appointment, $data, $actor): KeyHandoverAppointment {
            /** @var KeyHandoverAppointment $locked */
            $locked = KeyHandoverAppointment::query()
                ->lockForUpdate()
                ->findOrFail($appointment->getKey());

            if (! in_array($locked->status, [
                KeyHandoverStatus::Scheduled,
                KeyHandoverStatus::Rescheduled,
            ], true)) {
                throw ValidationException::withMessages([
                    'appointment' => 'Só entregas agendadas podem ser reagendadas.',
                ]);
            }

            $locked->fill($data);

            if (! $locked->isDirty()) {
                return $locked;
            }

            $locked->forceFill([
                'status' => KeyHandoverStatus::Rescheduled,
                'rescheduled_at' => now(),
            ])->save();

            $this->audit->record(
                AuditEvents::UPDATE,
                $locked,
                'allocations',
                'key_handover_reschedule',
                'Entrega de chaves reagendada.',
                metadata: ['actor_id' => $actor->id],
            );

            return $locked->refresh();
        });
    }

    public function complete(KeyHandoverAppointment $appointment, User $actor, ?string $notes = null): KeyHandoverAppointment
    {
        return DB::transaction(function () use ($appointment, $actor, $notes): KeyHandoverAppointment {
            /** @var KeyHandoverAppointment $locked */
            $locked = KeyHandoverAppointment::query()
                ->lockForUpdate()
                ->findOrFail($appointment->getKey());

            if ($locked->status === KeyHandoverStatus::Completed) {
                return $locked;
            }

            if (! in_array($locked->status, [
                KeyHandoverStatus::Scheduled,
                KeyHandoverStatus::Rescheduled,
            ], true)) {
                throw ValidationException::withMessages([
                    'appointment' => 'A entrega de chaves não pode ser concluída neste estado.',
                ]);
            }

            $locked->forceFill([
                'status' => KeyHandoverStatus::Completed,
                'completed_at' => now(),
                'completed_by' => $actor->id,
                'internal_notes' => $notes ?? $locked->internal_notes,
            ])->save();

            $this->audit->record(
                AuditEvents::APPROVE,
                $locked,
                'allocations',
                'key_handover_complete',
                'Entrega de chaves concluída.',
                metadata: ['actor_id' => $actor->id],
            );

            return $locked->refresh();
        });
    }

    public function cancel(KeyHandoverAppointment $appointment, User $actor, string $reason): KeyHandoverAppointment
    {
        return DB::transaction(function () use ($appointment, $actor, $reason): KeyHandoverAppointment {
            /** @var KeyHandoverAppointment $locked */
            $locked = KeyHandoverAppointment::query()
                ->lockForUpdate()
                ->findOrFail($appointment->getKey());

            if ($locked->status === KeyHandoverStatus::Cancelled) {
                return $locked;
            }

            if (! in_array($locked->status, [
                KeyHandoverStatus::Scheduled,
                KeyHandoverStatus::Rescheduled,
            ], true)) {
                throw ValidationException::withMessages([
                    'appointment' => 'A entrega de chaves não pode ser cancelada neste estado.',
                ]);
            }

            $locked->forceFill([
                'status' => KeyHandoverStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancellation_reason' => $reason,
            ])->save();

            $this->audit->record(
                AuditEvents::UPDATE,
                $locked,
                'allocations',
                'key_handover_cancel',
                'Entrega de chaves cancelada.',
                metadata: ['actor_id' => $actor->id],
            );

            return $locked->refresh();
        });
    }
}
