<?php

declare(strict_types=1);

namespace App\Services\Visits;

use App\Enums\AuditEventSeverity;
use App\Enums\PublicVisitBookingStatus;
use App\Enums\VisitSlotStatus;
use App\Jobs\DeliverPublicVisitBookingConfirmation;
use App\Models\Contest;
use App\Models\HousingUnit;
use App\Models\PublicVisitBooking;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use App\Services\Municipalities\VisitMunicipalContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class PublicVisitBookingService
{
    public function __construct(
        private readonly VisitMunicipalContextService $municipalContext,
        private readonly PublicVisitBookingAuditService $audit,
    ) {}

    /**
     * @return Collection<int, VisitSlot>
     */
    public function slotsFor(HousingUnit $housingUnit): Collection
    {
        if (! (bool) config('public_visits.enabled', true)) {
            return new Collection;
        }

        return VisitSlot::query()
            ->available()
            ->where('housing_unit_id', $housingUnit->getKey())
            ->where('municipality_id', $housingUnit->municipality_id)
            ->whereHas(
                'availability',
                function (Builder $query) use ($housingUnit): void {
                    $query
                        ->where('is_active', true)
                        ->where('housing_unit_id', $housingUnit->getKey())
                        ->where(
                            'municipality_id',
                            $housingUnit->municipality_id,
                        );
                },
            )
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('contest_id')
                    ->orWhereHas(
                        'contest',
                        static function (Builder $contest): void {
                            /** @var Builder<Contest> $contest */
                            (new Contest)->scopePubliclyVisible($contest);
                        },
                    );
            })
            ->with(['contest', 'availability'])
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{booking: PublicVisitBooking, cancellation_token: string}
     */
    public function book(HousingUnit $housingUnit, array $data): array
    {
        $guestCount = (int) $data['guest_count'];
        $normalizedEmail = Str::lower(trim((string) $data['email']));
        $token = bin2hex(random_bytes(32));

        try {
            $booking = DB::transaction(
                function () use (
                    $housingUnit,
                    $data,
                    $guestCount,
                    $normalizedEmail,
                    $token,
                ): PublicVisitBooking {
                    $slot = VisitSlot::query()
                        ->whereKey((int) $data['visit_slot_id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    $unitQuery = HousingUnit::query();
                    $unit = (new HousingUnit)
                        ->scopePubliclyVisible($unitQuery)
                        ->whereKey($housingUnit->getKey())
                        ->firstOrFail();

                    $municipalityId = $this->municipalContext
                        ->validateSlot($slot);

                    $this->assertPublicContext(
                        $unit,
                        $slot,
                        $municipalityId,
                    );
                    $this->assertCapacity($slot, $guestCount);

                    $fingerprint = $this->fingerprint(
                        $normalizedEmail,
                        (int) $slot->getKey(),
                    );

                    if (PublicVisitBooking::query()
                        ->where('active_fingerprint', $fingerprint)
                        ->exists()) {
                        throw ValidationException::withMessages([
                            'email' => 'Já existe uma marcação ativa para este endereço de email neste horário.',
                        ]);
                    }

                    $this->reserveCapacity($slot, $guestCount);

                    $booking = new PublicVisitBooking;
                    $booking->forceFill([
                        'booking_reference' => 'PVB-'
                            .Str::upper((string) Str::ulid()),
                        'municipality_id' => $municipalityId,
                        'visit_slot_id' => $slot->getKey(),
                        'housing_unit_id' => $unit->getKey(),
                        'contest_id' => $slot->contest_id,
                        'status' => PublicVisitBookingStatus::Booked,
                        'contact_name' => trim((string) $data['name']),
                        'contact_email' => $normalizedEmail,
                        'contact_phone' => $this->nullableString(
                            $data['phone'] ?? null,
                        ),
                        'email_hash' => $this->emailHash($normalizedEmail),
                        'active_fingerprint' => $fingerprint,
                        'guest_count' => $guestCount,
                        'cancellation_token_hash' => hash('sha256', $token),
                        'cancellation_token' => $token,
                        'cancellation_token_expires_at' => $slot->starts_at,
                        'privacy_notice_accepted_at' => now(),
                        'privacy_notice_version' => (string) config(
                            'public_visits.privacy_notice_version',
                            '2026-07-30',
                        ),
                        'booking_source' => 'public_portal',
                        'booked_at' => now(),
                        'retention_due_at' => $slot->ends_at?->copy()
                            ->addMonths(max(
                                1,
                                (int) config(
                                    'public_visits.retention_months',
                                    6,
                                ),
                            )),
                    ]);
                    $booking->save();

                    $this->audit->record(
                        'public_visit_booking_created',
                        $booking,
                        'Marcação pública de visita criada.',
                        [
                            'visit_slot_id' => (int) $slot->getKey(),
                            'housing_unit_id' => (int) $unit->getKey(),
                            'guest_count' => $guestCount,
                            'source' => 'public_portal',
                        ],
                    );

                    return $booking->refresh();
                },
                3,
            );
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                throw ValidationException::withMessages([
                    'email' => 'Já existe uma marcação ativa para este endereço de email neste horário.',
                ]);
            }

            throw $exception;
        }

        try {
            DeliverPublicVisitBookingConfirmation::dispatch(
                $booking->getKey(),
            )
                ->onQueue((string) config(
                    'public_visits.queue',
                    'communications',
                ))
                ->afterCommit();
        } catch (Throwable $exception) {
            $booking->forceFill([
                'confirmation_failed_at' => now(),
                'confirmation_error_code' => class_basename($exception),
            ])->save();

            $this->audit->record(
                'public_visit_booking_confirmation_dispatch_failed',
                $booking,
                'Não foi possível colocar a confirmação da marcação pública na fila.',
                [
                    'channel' => 'email',
                    'error_code' => class_basename($exception),
                ],
                severity: AuditEventSeverity::Warning,
            );
        }

        return [
            'booking' => $booking,
            'cancellation_token' => $token,
        ];
    }

    public function findByCancellationToken(
        string $token,
    ): PublicVisitBooking {
        return PublicVisitBooking::query()
            ->where('cancellation_token_hash', hash('sha256', $token))
            ->whereNull('anonymized_at')
            ->with(['slot', 'housingUnit', 'contest'])
            ->firstOrFail();
    }

    public function cancelByToken(string $token): PublicVisitBooking
    {
        $booking = $this->findByCancellationToken($token);

        return $this->cancel(
            booking: $booking,
            actor: null,
            notes: 'Cancelamento efetuado pelo titular através do token público.',
            enforceCutoff: true,
        );
    }

    public function cancelBackoffice(
        PublicVisitBooking $booking,
        User $actor,
        ?string $notes = null,
    ): PublicVisitBooking {
        return $this->cancel(
            booking: $booking,
            actor: $actor,
            notes: $notes,
            enforceCutoff: false,
        );
    }

    public function markAttended(
        PublicVisitBooking $booking,
        User $actor,
        ?string $notes = null,
    ): PublicVisitBooking {
        return $this->transition(
            $booking,
            PublicVisitBookingStatus::Attended,
            $actor,
            $notes,
        );
    }

    public function markNoShow(
        PublicVisitBooking $booking,
        User $actor,
        ?string $notes = null,
    ): PublicVisitBooking {
        return $this->transition(
            $booking,
            PublicVisitBookingStatus::NoShow,
            $actor,
            $notes,
        );
    }

    private function cancel(
        PublicVisitBooking $booking,
        ?User $actor,
        ?string $notes,
        bool $enforceCutoff,
    ): PublicVisitBooking {
        return DB::transaction(
            function () use (
                $booking,
                $actor,
                $notes,
                $enforceCutoff,
            ): PublicVisitBooking {
                $slot = VisitSlot::query()
                    ->whereKey($booking->visit_slot_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $locked = PublicVisitBooking::query()
                    ->whereKey($booking->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status === PublicVisitBookingStatus::Cancelled) {
                    return $locked->refresh();
                }

                if (! $locked->isActive()) {
                    throw ValidationException::withMessages([
                        'booking' => 'A marcação já não pode ser cancelada.',
                    ]);
                }

                if ($enforceCutoff && ! $this->canPubliclyCancel($slot)) {
                    throw ValidationException::withMessages([
                        'booking' => 'O prazo para cancelamento online já terminou. Contacte os serviços municipais.',
                    ]);
                }

                $this->releaseCapacity(
                    $slot,
                    (int) $locked->guest_count,
                );

                $locked->forceFill([
                    'status' => PublicVisitBookingStatus::Cancelled,
                    'cancelled_at' => now(),
                    'active_fingerprint' => null,
                    'cancellation_token' => null,
                    'status_notes' => $notes,
                    'status_changed_by' => $actor?->id,
                ])->save();

                $this->audit->record(
                    'public_visit_booking_cancelled',
                    $locked,
                    'Marcação pública de visita cancelada.',
                    [
                        'visit_slot_id' => (int) $slot->getKey(),
                        'guest_count' => (int) $locked->guest_count,
                        'source' => $actor instanceof User
                            ? 'backoffice'
                            : 'public_token',
                    ],
                    $actor,
                );

                return $locked->refresh();
            },
            3,
        );
    }

    private function transition(
        PublicVisitBooking $booking,
        PublicVisitBookingStatus $status,
        User $actor,
        ?string $notes,
    ): PublicVisitBooking {
        return DB::transaction(
            function () use (
                $booking,
                $status,
                $actor,
                $notes,
            ): PublicVisitBooking {
                $locked = PublicVisitBooking::query()
                    ->whereKey($booking->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status === $status) {
                    return $locked->refresh();
                }

                if (! $locked->isActive()) {
                    throw ValidationException::withMessages([
                        'booking' => 'A marcação já não admite esta alteração.',
                    ]);
                }

                $locked->forceFill([
                    'status' => $status,
                    'active_fingerprint' => null,
                    'cancellation_token' => null,
                    'status_notes' => $notes,
                    'status_changed_by' => $actor->id,
                ])->save();

                $this->audit->record(
                    'public_visit_booking_'.$status->value,
                    $locked,
                    'Estado da marcação pública de visita atualizado.',
                    [
                        'status' => $status->value,
                        'visit_slot_id' => (int) $locked->visit_slot_id,
                    ],
                    $actor,
                );

                return $locked->refresh();
            },
            3,
        );
    }

    private function assertPublicContext(
        HousingUnit $housingUnit,
        VisitSlot $slot,
        int $municipalityId,
    ): void {
        $availability = $slot->availability;

        if (! $availability instanceof VisitAvailability
            || (int) $housingUnit->municipality_id !== $municipalityId
            || (int) $slot->housing_unit_id !== (int) $housingUnit->getKey()
            || (int) $slot->municipality_id !== $municipalityId
            || ! $availability->is_active
            || (int) $availability->housing_unit_id
                !== (int) $housingUnit->getKey()) {
            throw ValidationException::withMessages([
                'visit_slot_id' => 'O horário selecionado não pertence a este fogo.',
            ]);
        }

        if ($slot->contest_id !== null
            && ! Contest::query()
                ->publiclyVisible()
                ->whereKey($slot->contest_id)
                ->exists()) {
            throw ValidationException::withMessages([
                'visit_slot_id' => 'O horário selecionado já não está disponível publicamente.',
            ]);
        }
    }

    private function assertCapacity(VisitSlot $slot, int $guestCount): void
    {
        if (! $slot->isBookable()
            || $guestCount < 1
            || $guestCount > $slot->remainingCapacity()) {
            throw ValidationException::withMessages([
                'guest_count' => 'O horário já não possui capacidade para o número de visitantes indicado.',
            ]);
        }
    }

    private function reserveCapacity(VisitSlot $slot, int $guestCount): void
    {
        $nextCount = (int) $slot->booked_count + $guestCount;
        $slot->forceFill([
            'booked_count' => $nextCount,
            'status' => $nextCount >= (int) $slot->capacity
                ? VisitSlotStatus::Full
                : VisitSlotStatus::Reserved,
        ])->save();
    }

    private function releaseCapacity(VisitSlot $slot, int $guestCount): void
    {
        $nextCount = max(
            0,
            (int) $slot->booked_count - $guestCount,
        );
        $slot->forceFill([
            'booked_count' => $nextCount,
            'status' => $nextCount === 0
                ? VisitSlotStatus::Available
                : VisitSlotStatus::Reserved,
        ])->save();
    }

    private function canPubliclyCancel(VisitSlot $slot): bool
    {
        $cutoff = max(
            0,
            (int) config(
                'public_visits.cancellation_cutoff_minutes',
                60,
            ),
        );

        return $slot->starts_at !== null
            && now()->lt($slot->starts_at->copy()->subMinutes($cutoff));
    }

    private function emailHash(string $email): string
    {
        return hash_hmac(
            'sha256',
            $email,
            $this->keyMaterial(),
        );
    }

    private function fingerprint(string $email, int $slotId): string
    {
        return hash_hmac(
            'sha256',
            $email.'|'.$slotId,
            $this->keyMaterial(),
        );
    }

    private function keyMaterial(): string
    {
        return hash(
            'sha256',
            (string) config('app.key'),
            true,
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== ''
            ? trim($value)
            : null;
    }

    private function isUniqueConstraintViolation(
        QueryException $exception,
    ): bool {
        return in_array(
            (string) $exception->getCode(),
            ['23000', '23505'],
            true,
        );
    }
}
