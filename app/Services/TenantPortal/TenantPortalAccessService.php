<?php

namespace App\Services\TenantPortal;

use App\Enums\ContractOccupancyStatus;
use App\Enums\ContractStatus;
use App\Enums\TenantPortalStatus;
use App\Models\Contract;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TenantPortalAccessService
{
    public function hasActiveAccess(User $user): bool
    {
        return $this->activeContracts($user)->exists()
            || TenantProfile::query()
                ->where('user_id', $user->id)
                ->where('status', TenantPortalStatus::Active->value)
                ->exists();
    }

    public function ensureForUser(User $tenant, ?User $actor = null): TenantProfile
    {
        return DB::transaction(function () use ($tenant, $actor) {
            $profile = TenantProfile::query()->firstOrNew(['user_id' => $tenant->id]);

            if (! $profile->exists || $profile->status !== TenantPortalStatus::Active) {
                $profile->forceFill([
                    'status' => TenantPortalStatus::Active,
                    'activated_at' => $profile->activated_at ?? now(),
                    'activation_notes' => $profile->activation_notes ?? 'Perfil ativado por existência de contrato pós-atribuição.',
                    'created_by' => $profile->exists ? $profile->created_by : $actor?->id,
                    'updated_by' => $actor?->id,
                ])->save();
            }

            foreach ($this->activeContracts($tenant)->get() as $contract) {
                $profile->contractAccesses()->updateOrCreate(
                    ['lease_contract_id' => $contract->id],
                    [
                        'user_id' => $tenant->id,
                        'housing_unit_id' => $contract->housing_unit_id,
                        'status' => ContractOccupancyStatus::Active,
                        'starts_on' => $contract->start_date,
                        'ends_on' => $contract->end_date,
                        'granted_at' => now(),
                        'granted_by' => $actor?->id,
                    ],
                );
            }

            return $profile->refresh();
        });
    }

    /**
     * @return Builder<Contract>
     */
    public function activeContracts(User $tenant): Builder
    {
        return Contract::query()
            ->forCandidate($tenant)
            ->whereIn('status', [
                ContractStatus::Active->value,
                ContractStatus::Suspended->value,
                ContractStatus::Renewed->value,
            ]);
    }

    public function canAccessContract(User $tenant, Contract $contract): bool
    {
        return (int) $contract->user_id === (int) $tenant->id
            && in_array($contract->status, [
                ContractStatus::Active,
                ContractStatus::Suspended,
                ContractStatus::Renewed,
                ContractStatus::Expired,
                ContractStatus::Ended,
            ], true);
    }
}
