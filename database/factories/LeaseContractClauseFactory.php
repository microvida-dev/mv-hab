<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\LeaseContractClause;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaseContractClause> */
class LeaseContractClauseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lease_contract_id' => Contract::factory(),
            'code' => 'DEMO',
            'title' => 'Cláusula snapshot',
            'body' => 'Texto fictício de snapshot contratual.',
            'category' => 'general',
            'sort_order' => 1,
        ];
    }
}
