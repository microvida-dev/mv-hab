<?php

namespace Database\Factories;

use App\Enums\ContractSignatureMethod;
use App\Enums\ContractSignatureRole;
use App\Enums\ContractSignatureStatus;
use App\Models\Contract;
use App\Models\LeaseContractSignature;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaseContractSignature> */
class LeaseContractSignatureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lease_contract_id' => Contract::factory(),
            'signature_role' => ContractSignatureRole::Tenant->value,
            'status' => ContractSignatureStatus::Signed->value,
            'signed_by_name' => 'Signatário de Teste',
            'signed_at' => now(),
            'signature_method' => ContractSignatureMethod::Manual->value,
        ];
    }
}
