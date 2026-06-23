<?php

namespace Database\Factories;

use App\Enums\ContractPartyType;
use App\Models\Contract;
use App\Models\LeaseContractParty;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaseContractParty> */
class LeaseContractPartyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lease_contract_id' => Contract::factory(),
            'party_type' => ContractPartyType::Tenant->value,
            'name' => 'Parte contratual fictícia',
            'sort_order' => 1,
        ];
    }
}
