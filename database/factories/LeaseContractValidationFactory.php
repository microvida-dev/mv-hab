<?php

namespace Database\Factories;

use App\Enums\ContractValidationStatus;
use App\Enums\ContractValidationType;
use App\Models\Contract;
use App\Models\LeaseContractValidation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaseContractValidation> */
class LeaseContractValidationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lease_contract_id' => Contract::factory(),
            'validated_by' => User::factory(),
            'status' => ContractValidationStatus::Approved->value,
            'validation_type' => ContractValidationType::Final->value,
            'summary' => 'Validação fictícia.',
            'validated_at' => now(),
        ];
    }
}
