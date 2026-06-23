<?php

namespace Database\Factories;

use App\Enums\DocumentReviewDecision;
use App\Enums\DocumentStatus;
use App\Models\DocumentReview;
use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentReview>
 */
class DocumentReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_submission_id' => DocumentSubmission::factory(),
            'reviewed_by' => User::factory(),
            'from_status' => DocumentStatus::Submitted->value,
            'to_status' => DocumentStatus::Validated->value,
            'decision' => DocumentReviewDecision::Validated->value,
            'reason' => null,
            'internal_notes' => 'Nota interna fictícia.',
        ];
    }
}
