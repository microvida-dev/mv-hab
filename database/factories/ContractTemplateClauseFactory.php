<?php

namespace Database\Factories;

use App\Models\ContractClause;
use App\Models\ContractTemplate;
use App\Models\ContractTemplateClause;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContractTemplateClause> */
class ContractTemplateClauseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'contract_template_id' => ContractTemplate::factory(),
            'contract_clause_id' => ContractClause::factory(),
            'sort_order' => 1,
            'is_active' => true,
        ];
    }
}
