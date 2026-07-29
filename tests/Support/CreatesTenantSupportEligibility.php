<?php

namespace Tests\Support;

use App\Enums\ContractStatus;
use App\Enums\KeyHandoverStatus;
use App\Enums\TenantPortalStatus;
use App\Enums\TenantTransitionStatus;
use App\Models\Allocation;
use App\Models\Application;
use App\Models\Contract;
use App\Models\KeyHandoverAppointment;
use App\Models\TenantProfile;
use App\Models\TenantTransition;
use App\Models\User;

trait CreatesTenantSupportEligibility
{
    protected function enableTenantSupportFor(
        User $candidate,
    ): TenantTransition {
        $application = Application::factory()
            ->submitted()
            ->create(['user_id' => $candidate->id]);
        $allocation = Allocation::factory()->create([
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
        ]);
        $contract = Contract::factory()->create([
            'user_id' => $candidate->id,
            'application_id' => $application->id,
            'allocation_id' => $allocation->id,
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
            'status' => ContractStatus::Active->value,
            'activated_at' => now(),
        ]);
        $handover = KeyHandoverAppointment::factory()->create([
            'application_id' => $application->id,
            'allocation_id' => $allocation->id,
            'user_id' => $candidate->id,
            'contest_id' => $application->contest_id,
            'housing_unit_id' => $contract->housing_unit_id,
            'status' => KeyHandoverStatus::Completed->value,
            'completed_at' => now(),
        ]);

        $profile = TenantProfile::query()
            ->withTrashed()
            ->firstOrNew([
                'user_id' => $candidate->id,
            ]);

        $profile->forceFill([
            'status' => TenantPortalStatus::Active->value,
            'activated_at' => now(),
            'blocked_at' => null,
            'archived_at' => null,
            'activation_notes' => 'Perfil ativo para teste do apoio pós-entrega.',
            'deleted_at' => null,
        ])->save();

        return TenantTransition::factory()->create([
            'winner_registration_id' => $handover->winner_registration_id,
            'key_handover_appointment_id' => $handover->id,
            'allocation_id' => $allocation->id,
            'lease_contract_id' => $contract->id,
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'housing_unit_id' => $contract->housing_unit_id,
            'status' => TenantTransitionStatus::Completed->value,
            'completed_at' => now(),
        ]);
    }
}
