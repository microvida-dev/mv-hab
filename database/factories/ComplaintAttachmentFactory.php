<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\DocumentSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ComplaintAttachment> */
class ComplaintAttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'complaint_id' => Complaint::factory(),
            'document_submission_id' => DocumentSubmission::factory(),
            'uploaded_by' => User::factory(),
            'description' => 'Anexo fictício.',
        ];
    }
}
