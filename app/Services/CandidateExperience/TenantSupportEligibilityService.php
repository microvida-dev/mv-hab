<?php

namespace App\Services\CandidateExperience;

use App\Enums\ContractStatus;
use App\Enums\KeyHandoverStatus;
use App\Enums\TenantPortalStatus;
use App\Enums\TenantTransitionStatus;
use App\Models\TenantTransition;
use App\Models\User;

final class TenantSupportEligibilityService
{
    public function isAvailableFor(User $user): bool
    {
        if (
            ! (bool) config(
                'mvhab.candidate_experience_runtime.tenant_support',
                true,
            )
            || ! $user->hasRole('candidate')
        ) {
            return false;
        }

        $profileIsActive = $user->tenantProfile()
            ->where('status', TenantPortalStatus::Active->value)
            ->whereNotNull('activated_at')
            ->exists();

        if (! $profileIsActive) {
            return false;
        }

        return TenantTransition::query()
            ->where('user_id', $user->id)
            ->where('status', TenantTransitionStatus::Completed->value)
            ->whereNotNull('completed_at')
            ->whereNotNull('allocation_id')
            ->whereNotNull('lease_contract_id')
            ->whereNotNull('key_handover_appointment_id')
            ->whereHas(
                'leaseContract',
                fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('status', ContractStatus::Active->value)
                    ->whereNotNull('activated_at'),
            )
            ->whereHas(
                'keyHandoverAppointment',
                fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where(
                        'status',
                        KeyHandoverStatus::Completed->value,
                    )
                    ->whereNotNull('completed_at'),
            )
            ->exists();
    }
}
