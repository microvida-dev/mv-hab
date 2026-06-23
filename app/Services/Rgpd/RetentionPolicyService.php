<?php

namespace App\Services\Rgpd;

use App\Models\RetentionPolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RetentionPolicyService
{
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
        return RetentionPolicy::query()->create([
            ...$data,
            'created_by' => $actor?->id,
        ]);
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
    public function update(RetentionPolicy $policy, array $data): RetentionPolicy
    {
        $policy->forceFill($data)->save();

        return $policy->refresh();
    }
}
