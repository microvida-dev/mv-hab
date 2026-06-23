<?php

namespace Database\Factories;

use App\Enums\ContractTemplateStatus;
use App\Models\ContractTemplate;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContractTemplate> */
class ContractTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'name' => 'MINUTA DEMO - SUJEITA A VALIDACAO JURIDICA',
            'status' => ContractTemplateStatus::Active->value,
            'version_number' => 1,
            'template_body' => 'MINUTA DEMO - SUJEITA A VALIDACAO JURIDICA. Contrato {{contract.number}} para {{tenant.name}}, habitação {{housing.address}}, renda {{rent.amount}}, caução {{deposit.amount}}. {{clauses}}',
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
