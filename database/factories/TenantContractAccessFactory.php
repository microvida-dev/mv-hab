<?php

namespace Database\Factories;

use App\Enums\ContractOccupancyStatus;
use App\Models\Contract;
use App\Models\TenantContractAccess;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TenantContractAccess> */
class TenantContractAccessFactory extends Factory
{
    protected $model = TenantContractAccess::class;

    public function definition(): array
    {
        $tenant = User::factory()->create();
        $contract = Contract::factory()->create(['user_id' => $tenant->id]);
        $profile = TenantProfile::factory()->create(['user_id' => $tenant->id]);

        return [
            'tenant_profile_id' => $profile->id,
            'user_id' => $tenant->id,
            'lease_contract_id' => $contract->id,
            'housing_unit_id' => $contract->housing_unit_id,
            'status' => ContractOccupancyStatus::Active->value,
            'starts_on' => $contract->start_date,
            'ends_on' => $contract->end_date,
            'granted_at' => now(),
        ];
    }
}
