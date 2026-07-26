<?php

namespace App\Services\Rgpd;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\RetentionPolicy;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Database\Eloquent\Model;

class RetentionPolicyService
{
    public function __construct(
        private readonly AuditTrailService $audit,
        private readonly PrivacyMunicipalScopeService $scope,
    ) {}

    /**
     * @param array{
     *     code?: string,
     *     name?: string,
     *     description?: string|null,
     *     status?: string,
     *     entity_type?: class-string<Model>|string,
     *     document_type_id?: int|null,
     *     retention_period_months?: int,
     *     retention_action?: string,
     *     legal_basis?: string|null,
     *     requires_manual_approval?: bool
     * } $data
     */
    public function create(array $data, ?User $actor = null): RetentionPolicy
    {
        abort_unless($actor instanceof User && $actor->municipality_id !== null, 403);

        $policy = RetentionPolicy::query()->create([
            ...$data,
            'municipality_id' => $actor->municipality_id,
            'created_by' => $actor->id,
        ]);

        $this->audit->record(
            'retention_policy.created',
            $policy,
            AuditEventCategory::Rgpd,
            AuditEventSeverity::Notice,
            'Política municipal de retenção criada.',
            actor: $actor,
        );

        return $policy;
    }

    /**
     * @param array{
     *     code?: string,
     *     name?: string,
     *     description?: string|null,
     *     status?: string,
     *     entity_type?: class-string<Model>|string,
     *     document_type_id?: int|null,
     *     retention_period_months?: int,
     *     retention_action?: string,
     *     legal_basis?: string|null,
     *     requires_manual_approval?: bool
     * } $data
     */
    public function update(RetentionPolicy $policy, array $data, ?User $actor = null): RetentionPolicy
    {
        abort_unless(
            $actor instanceof User
            && $this->scope->ownsMutableRetentionPolicy($actor, $policy),
            403,
        );

        $old = $policy->toArray();
        $policy->forceFill($data)->save();
        $this->audit->record(
            'retention_policy.updated',
            $policy,
            AuditEventCategory::Rgpd,
            AuditEventSeverity::Notice,
            'Política municipal de retenção atualizada.',
            oldValues: $old,
            newValues: $policy->toArray(),
            actor: $actor,
        );

        return $policy->refresh();
    }
}
