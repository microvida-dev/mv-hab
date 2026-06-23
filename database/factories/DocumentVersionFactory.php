<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_submission_id' => DocumentSubmission::factory(),
            'version_number' => 1,
            'original_filename' => 'documento-teste.pdf',
            'stored_filename' => fake()->uuid().'.pdf',
            'storage_disk' => 'local',
            'storage_path' => 'documents/test/documento-teste.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'checksum' => hash('sha256', fake()->uuid()),
            'uploaded_by' => User::factory(),
            'uploaded_at' => now(),
            'status_at_upload' => DocumentStatus::Submitted->value,
        ];
    }
}
