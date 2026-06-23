<?php

namespace App\Services\Complaints;

use App\Enums\ComplaintReviewResult;
use App\Models\Complaint;
use App\Models\ComplaintReview;
use App\Models\User;

class ComplaintReviewService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function record(Complaint $complaint, User $reviewer, array $data): ComplaintReview
    {
        $review = new ComplaintReview([
            'status' => $data['status'] ?? 'completed',
            'result' => $data['result'] ? ComplaintReviewResult::from($data['result']) : null,
            'summary' => $data['summary'] ?? null,
            'technical_notes' => $data['technical_notes'] ?? null,
            'started_at' => $data['started_at'] ?? now(),
            'completed_at' => $data['completed_at'] ?? now(),
        ]);
        $review->forceFill([
            'complaint_id' => $complaint->id,
            'reviewed_by' => $reviewer->id,
        ])->save();

        return $review;
    }
}
