<?php

namespace Database\Factories;

use App\Enums\ContractClauseStatus;
use App\Models\ContractClause;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContractClause> */
class ContractClauseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'code' => 'DEMO-GERAL',
            'title' => 'Cláusula demo',
            'body' => 'Cláusula fictícia sujeita a validação jurídica.',
            'category' => 'general',
            'status' => ContractClauseStatus::Active->value,
            'is_mandatory' => true,
            'sort_order' => 1,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
