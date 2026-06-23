<?php

namespace Database\Factories;

use App\Enums\ContractDocumentStatus;
use App\Enums\ContractDocumentType;
use App\Models\Contract;
use App\Models\LeaseContractDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaseContractDocument> */
class LeaseContractDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lease_contract_id' => Contract::factory(),
            'status' => ContractDocumentStatus::Generated->value,
            'document_type' => ContractDocumentType::ContractHtml->value,
            'version_number' => 1,
            'title' => 'Contrato demo',
            'html_content' => '<html><body>Contrato demo</body></html>',
            'storage_disk' => 'local',
            'storage_path' => 'contracts/demo.html',
            'mime_type' => 'text/html',
            'file_size' => 39,
            'checksum' => hash('sha256', 'demo'),
            'generated_at' => now(),
        ];
    }
}
