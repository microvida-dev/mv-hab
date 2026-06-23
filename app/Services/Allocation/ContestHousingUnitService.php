<?php

namespace App\Services\Allocation;

use App\Enums\ContestHousingUnitStatus;
use App\Models\ContestHousingUnit;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Validation\ValidationException;

class ContestHousingUnitService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): ContestHousingUnit
    {
        $this->assertProgramOrContest($data);
        $this->assertNoActiveConflict((int) $data['housing_unit_id'], $data['contest_id'] ?? null);

        $unit = new ContestHousingUnit($data);
        $unit->forceFill([
            'status' => $data['status'] ?? ContestHousingUnitStatus::Available,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $unit, 'allocations', 'contest_housing_unit_create', 'Habitação associada ao concurso.');

        return $unit->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ContestHousingUnit $unit, array $data, User $actor): ContestHousingUnit
    {
        $this->assertProgramOrContest($data + $unit->only(['program_id', 'contest_id']));

        if (isset($data['housing_unit_id']) && (int) $data['housing_unit_id'] !== $unit->housing_unit_id) {
            $this->assertNoActiveConflict((int) $data['housing_unit_id'], $data['contest_id'] ?? $unit->contest_id, $unit);
        }

        $unit->fill($data);
        $unit->forceFill(['updated_by' => $actor->id])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $unit, 'allocations', 'contest_housing_unit_update', 'Habitação do concurso atualizada.');

        return $unit->refresh();
    }

    public function markAvailable(ContestHousingUnit $unit, User $actor): ContestHousingUnit
    {
        $this->assertNoActiveConflict($unit->housing_unit_id, $unit->contest_id, $unit);

        return $this->setStatus($unit, ContestHousingUnitStatus::Available, $actor, 'contest_housing_unit_available');
    }

    public function markReserved(ContestHousingUnit $unit, User $actor): ContestHousingUnit
    {
        return $this->setStatus($unit, ContestHousingUnitStatus::Reserved, $actor, 'contest_housing_unit_reserved');
    }

    public function markAllocated(ContestHousingUnit $unit, User $actor): ContestHousingUnit
    {
        return $this->setStatus($unit, ContestHousingUnitStatus::Allocated, $actor, 'contest_housing_unit_allocated');
    }

    public function markAccepted(ContestHousingUnit $unit, User $actor): ContestHousingUnit
    {
        return $this->setStatus($unit, ContestHousingUnitStatus::Accepted, $actor, 'contest_housing_unit_accepted');
    }

    public function markUnavailable(ContestHousingUnit $unit, User $actor): ContestHousingUnit
    {
        return $this->setStatus($unit, ContestHousingUnitStatus::Unavailable, $actor, 'contest_housing_unit_unavailable');
    }

    public function release(ContestHousingUnit $unit, User $actor): ContestHousingUnit
    {
        return $this->setStatus($unit, ContestHousingUnitStatus::Available, $actor, 'contest_housing_unit_release');
    }

    public function remove(ContestHousingUnit $unit, User $actor): ContestHousingUnit
    {
        if ($unit->allocations()->active()->exists()) {
            throw ValidationException::withMessages(['contest_housing_unit' => 'Não é possível remover uma habitação com atribuição ativa.']);
        }

        $unit->forceFill(['status' => ContestHousingUnitStatus::Removed, 'updated_by' => $actor->id])->save();
        $unit->delete();
        $this->auditLogger->record(AuditEvents::DELETE, $unit, 'allocations', 'contest_housing_unit_remove', 'Habitação removida do concurso.');

        return $unit;
    }

    public function assertAvailable(ContestHousingUnit $unit): void
    {
        if ($unit->status !== ContestHousingUnitStatus::Available) {
            throw ValidationException::withMessages(['contest_housing_unit' => 'A habitação não está disponível para atribuição.']);
        }

        if ($unit->availability_starts_at && now()->lt($unit->availability_starts_at)) {
            throw ValidationException::withMessages(['contest_housing_unit' => 'A disponibilidade da habitação ainda não começou.']);
        }

        if ($unit->availability_ends_at && now()->gt($unit->availability_ends_at)) {
            throw ValidationException::withMessages(['contest_housing_unit' => 'A disponibilidade da habitação expirou.']);
        }
    }

    private function setStatus(ContestHousingUnit $unit, ContestHousingUnitStatus $status, User $actor, string $action): ContestHousingUnit
    {
        $unit->forceFill(['status' => $status, 'updated_by' => $actor->id])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $unit, 'allocations', $action, 'Estado da habitação no concurso atualizado.');

        return $unit->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertProgramOrContest(array $data): void
    {
        if (empty($data['program_id']) && empty($data['contest_id'])) {
            throw ValidationException::withMessages(['contest_id' => 'Indique programa ou concurso.']);
        }
    }

    private function assertNoActiveConflict(int $housingUnitId, ?int $contestId = null, ?ContestHousingUnit $except = null): void
    {
        $query = ContestHousingUnit::query()
            ->where('housing_unit_id', $housingUnitId)
            ->whereIn('status', [
                ContestHousingUnitStatus::Available->value,
                ContestHousingUnitStatus::Reserved->value,
                ContestHousingUnitStatus::Allocated->value,
                ContestHousingUnitStatus::Accepted->value,
            ])
            ->when($contestId, fn ($query) => $query->where('contest_id', '!=', $contestId));

        if ($except !== null) {
            $query->whereKeyNot($except->getKey());
        }

        $conflict = $query->exists();

        if ($conflict) {
            throw ValidationException::withMessages(['housing_unit_id' => 'Esta habitação já está ativa noutro concurso incompatível.']);
        }
    }
}
