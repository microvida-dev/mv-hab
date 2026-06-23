<?php

namespace App\Services\Rgpd;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\ConsentPurpose;
use App\Models\User;
use App\Services\Audit\AuditTrailService;

class ConsentPurposeService
{
    public function __construct(private readonly AuditTrailService $audit) {}

    /**
     * @param array{
     *     code?: string,
     *     name?: string,
     *     description?: string,
     *     legal_basis?: string,
     *     is_required?: bool,
     *     is_active?: bool,
     *     requires_explicit_consent?: bool,
     *     retention_period_months?: int|null
     * } $data
     */
    public function create(array $data, ?User $actor = null): ConsentPurpose
    {
        $purpose = ConsentPurpose::query()->create([
            ...$data,
            'created_by' => $actor?->id,
            'updated_by' => $actor?->id,
        ]);

        $this->audit->record('consent_purpose.created', $purpose, AuditEventCategory::Rgpd, AuditEventSeverity::Notice, 'Finalidade de tratamento criada.', actor: $actor);

        return $purpose;
    }

    /**
     * @param array{
     *     code?: string,
     *     name?: string,
     *     description?: string,
     *     legal_basis?: string,
     *     is_required?: bool,
     *     is_active?: bool,
     *     requires_explicit_consent?: bool,
     *     retention_period_months?: int|null
     * } $data
     */
    public function update(ConsentPurpose $purpose, array $data, ?User $actor = null): ConsentPurpose
    {
        $old = $purpose->toArray();
        $purpose->forceFill([...$data, 'updated_by' => $actor?->id])->save();
        $this->audit->record('consent_purpose.updated', $purpose, AuditEventCategory::Rgpd, AuditEventSeverity::Notice, 'Finalidade de tratamento atualizada.', oldValues: $old, newValues: $purpose->toArray(), actor: $actor);

        return $purpose->refresh();
    }
}
