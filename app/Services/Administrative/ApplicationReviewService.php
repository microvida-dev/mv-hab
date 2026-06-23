<?php

namespace App\Services\Administrative;

use App\Enums\ApplicationReviewResult;
use App\Enums\ApplicationReviewStatus;
use App\Enums\ApplicationReviewType;
use App\Models\AdministrativeProcess;
use App\Models\ApplicationReview;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class ApplicationReviewService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(AdministrativeProcess $process, array $data, User $actor): ApplicationReview
    {
        return DB::transaction(function () use ($process, $data, $actor) {
            $review = new ApplicationReview([
                'review_type' => $data['review_type'] ?? ApplicationReviewType::Preliminary->value,
                'summary' => $data['summary'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);
            $review->forceFill([
                'administrative_process_id' => $process->id,
                'application_id' => $process->application_id,
                'status' => ApplicationReviewStatus::InProgress,
                'reviewed_by' => $actor->id,
                'started_at' => now(),
            ]);
            $review->save();

            foreach ($data['items'] ?? [] as $index => $item) {
                $review->items()->create([
                    'code' => $item['code'] ?? 'MANUAL-'.($index + 1),
                    'name' => $item['name'],
                    'category' => $item['category'] ?? 'manual',
                    'result' => $item['result'] ?? ApplicationReviewResult::RequiresManualReview->value,
                    'message' => $item['message'] ?? null,
                    'technical_message' => $item['technical_message'] ?? null,
                    'requires_correction' => (bool) ($item['requires_correction'] ?? false),
                    'correction_reason' => $item['correction_reason'] ?? null,
                ]);
            }

            $this->auditLogger->record(
                event: AuditEvents::CREATE,
                auditable: $review,
                module: 'administrative_processes',
                action: 'review_create',
                description: 'Análise administrativa criada.',
            );

            $review->load(['items', 'reviewedBy']);

            return $review;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function complete(ApplicationReview $review, array $data, User $actor): ApplicationReview
    {
        $review->forceFill([
            'status' => ApplicationReviewStatus::Completed,
            'result' => $data['result'],
            'summary' => $data['summary'] ?? $review->summary,
            'internal_notes' => $data['internal_notes'] ?? $review->internal_notes,
            'reviewed_by' => $actor->id,
            'completed_at' => now(),
        ])->save();

        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $review,
            module: 'administrative_processes',
            action: 'review_complete',
            description: 'Análise administrativa concluída.',
            newValues: ['result' => $review->result?->value],
        );

        return $review->refresh();
    }
}
