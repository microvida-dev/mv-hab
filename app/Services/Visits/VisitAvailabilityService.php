<?php

namespace App\Services\Visits;

use App\Models\User;
use App\Models\VisitAvailability;
use App\Services\Municipalities\VisitMunicipalContextService;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitAvailabilityService
{
    public function __construct(
        private readonly VisitAuditService $audit,
        private readonly VisitMunicipalContextService $municipalContext,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, User $actor): VisitAvailability
    {
        unset($data['municipality_id']);
        $this->validateWindow($data);

        return DB::transaction(function () use ($data, $actor): VisitAvailability {
            $municipalityId = $this->municipalContext
                ->municipalityForAvailabilityData($data, $actor);
            $this->ensureNoConflict($data, $municipalityId);

            $availability = new VisitAvailability($data);
            $availability->forceFill([
                'municipality_id' => $municipalityId,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
                'timezone' => $data['timezone'] ?? config('app.timezone', 'UTC'),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ])->save();

            $this->audit->availability(AuditEvents::CREATE, $availability, 'Disponibilidade de visitas criada.');

            return $availability->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(VisitAvailability $availability, array $data, User $actor): VisitAvailability
    {
        unset($data['municipality_id']);
        $payload = array_replace($availability->only([
            'contest_id',
            'housing_unit_id',
            'staff_user_id',
            'starts_at',
            'ends_at',
            'slot_duration_minutes',
            'capacity_per_slot',
            'buffer_minutes',
            'timezone',
            'is_active',
        ]), $data);
        $this->validateWindow($payload);

        return DB::transaction(function () use ($availability, $data, $actor, $payload): VisitAvailability {
            $lockedAvailability = VisitAvailability::query()
                ->whereKey($availability)
                ->lockForUpdate()
                ->firstOrFail();
            $municipalityId = $this->municipalContext
                ->municipalityForAvailabilityData(
                    $payload,
                    $actor,
                    $lockedAvailability,
                );
            $this->ensureNoConflict(
                $payload,
                $municipalityId,
                $lockedAvailability,
            );

            $lockedAvailability->fill($data);
            $lockedAvailability->forceFill([
                'municipality_id' => $municipalityId,
                'updated_by' => $actor->id,
            ])->save();
            $this->audit->availability(AuditEvents::UPDATE, $lockedAvailability, 'Disponibilidade de visitas atualizada.');

            return $lockedAvailability->refresh();
        });
    }

    public function activate(VisitAvailability $availability, User $actor): VisitAvailability
    {
        return $this->setActive($availability, $actor, true);
    }

    public function deactivate(VisitAvailability $availability, User $actor): VisitAvailability
    {
        return $this->setActive($availability, $actor, false);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateWindow(array $data): void
    {
        if ((int) ($data['slot_duration_minutes'] ?? 0) <= 0 || (int) ($data['capacity_per_slot'] ?? 0) <= 0) {
            throw ValidationException::withMessages(['availability' => 'A duração e a capacidade devem ser positivas.']);
        }

        if (strtotime((string) ($data['starts_at'] ?? '')) >= strtotime((string) ($data['ends_at'] ?? ''))) {
            throw ValidationException::withMessages(['ends_at' => 'A data final deve ser posterior à data inicial.']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureNoConflict(
        array $data,
        int $municipalityId,
        ?VisitAvailability $current = null,
    ): void {
        $query = VisitAvailability::query()
            ->active()
            ->where('municipality_id', $municipalityId)
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at']);

        if ($current instanceof VisitAvailability) {
            $query->whereKeyNot($current->getKey());
        }

        $query->where(function (Builder $builder) use ($data): void {
            foreach ([
                'staff_user_id',
                'housing_unit_id',
                'contest_id',
            ] as $field) {
                if (! empty($data[$field])) {
                    $builder->orWhere($field, $data[$field]);
                }
            }
        });

        if ($query->exists()) {
            throw ValidationException::withMessages(['availability' => 'Já existe uma disponibilidade sobreposta para o técnico ou imóvel indicado.']);
        }
    }

    private function setActive(
        VisitAvailability $availability,
        User $actor,
        bool $active,
    ): VisitAvailability {
        return DB::transaction(function () use (
            $availability,
            $actor,
            $active,
        ): VisitAvailability {
            $lockedAvailability = VisitAvailability::query()
                ->whereKey($availability)
                ->lockForUpdate()
                ->firstOrFail();
            $this->municipalContext->validateAvailabilityForActor(
                $lockedAvailability,
                $actor,
            );
            $lockedAvailability->forceFill([
                'is_active' => $active,
                'updated_by' => $actor->id,
            ])->save();
            $this->audit->availability(
                AuditEvents::UPDATE,
                $lockedAvailability,
                $active
                    ? 'Disponibilidade de visitas ativada.'
                    : 'Disponibilidade de visitas desativada.',
            );

            return $lockedAvailability->refresh();
        });
    }
}
