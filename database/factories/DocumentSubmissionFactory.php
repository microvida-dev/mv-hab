<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\AdhesionRegistration;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\RequiredDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentSubmission>
 */
class DocumentSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_type_id' => DocumentType::factory(),
            'required_document_id' => null,
            'user_id' => User::factory(),
            'adhesion_registration_id' => AdhesionRegistration::factory(),
            'status' => DocumentStatus::Submitted->value,
            'title' => 'Documento fictício de teste',
            'original_filename' => 'documento-teste.pdf',
            'stored_filename' => fake()->uuid().'.pdf',
            'storage_disk' => 'local',
            'storage_path' => 'documents/test/documento-teste.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'checksum' => hash('sha256', fake()->uuid()),
            'submitted_at' => now(),
            'submitted_by' => null,
        ];
    }

    public function forRequiredDocument(RequiredDocument $requiredDocument): static
    {
        return $this->state(fn () => [
            'document_type_id' => $requiredDocument->document_type_id,
            'required_document_id' => $requiredDocument->id,
        ]);
    }
}
