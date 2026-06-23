<?php

namespace Database\Factories;

use App\Enums\CommunicationReceiptType;
use App\Models\CommunicationLog;
use App\Models\CommunicationReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunicationReceipt> */
class CommunicationReceiptFactory extends Factory
{
    public function definition(): array
    {
        $content = '<html><body>Comprovativo fictício.</body></html>';

        return [
            'communication_log_id' => CommunicationLog::factory(),
            'receipt_number' => 'REC-TEST-'.fake()->unique()->numerify('########'),
            'receipt_type' => CommunicationReceiptType::SendProof,
            'storage_disk' => 'local',
            'storage_path' => 'communications/receipts/test/fictitious.html',
            'mime_type' => 'text/html',
            'file_size' => strlen($content),
            'checksum' => hash('sha256', $content),
            'generated_at' => now(),
        ];
    }
}
