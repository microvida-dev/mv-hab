<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationDocument>
 */
class ApplicationDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'document_submission_id' => DocumentSubmission::factory(),
            'document_type_id' => DocumentType::factory(),
            'is_required' => true,
            'status_at_submission' => DocumentStatus::Submitted->value,
        ];
    }
}
