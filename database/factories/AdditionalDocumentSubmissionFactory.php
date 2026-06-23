<?php

namespace Database\Factories;

use App\Enums\AdditionalDocumentStatus;
use App\Models\AdditionalDocumentSubmission;
use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AdditionalDocumentSubmission> */
class AdditionalDocumentSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'user_id' => User::factory(),
            'status' => AdditionalDocumentStatus::Submitted->value,
            'title' => 'Documento submetido fictício',
            'file_disk' => 'local',
            'file_path' => 'additional-documents/demo/documento-ficticio.pdf',
            'original_name' => 'documento-ficticio.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'submitted_at' => now(),
        ];
    }
}
