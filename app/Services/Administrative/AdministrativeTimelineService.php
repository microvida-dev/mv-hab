<?php

namespace App\Services\Administrative;

use App\Enums\AdministrativeNoteVisibility;
use App\Models\AdministrativeDecision;
use App\Models\AdministrativeProcess;
use App\Models\AdministrativeProcessNote;
use App\Models\AdministrativeProcessStatusHistory;
use App\Models\AdministrativeTask;
use App\Models\Application;
use App\Models\ApplicationReview;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use Illuminate\Support\Collection;

class AdministrativeTimelineService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function forBackoffice(AdministrativeProcess $process): Collection
    {
        return $this->timeline($process, includeInternal: true);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function forCandidate(AdministrativeProcess $process): Collection
    {
        return $this->timeline($process, includeInternal: false);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function timeline(AdministrativeProcess $process, bool $includeInternal): Collection
    {
        $process->loadMissing([
            'application',
            'statusHistories.changedBy',
            'reviews',
            'correctionRequests.responses',
            'decisions',
            'tasks',
            'notes.user',
        ]);

        /** @var Application|null $application */
        $application = $process->getRelationValue('application');
        /** @var Collection<int, AdministrativeProcessStatusHistory> $statusHistories */
        $statusHistories = $process->statusHistories;
        /** @var Collection<int, CorrectionRequest> $correctionRequests */
        $correctionRequests = $process->correctionRequests;
        /** @var Collection<int, ApplicationReview> $reviews */
        $reviews = $process->reviews;
        /** @var Collection<int, AdministrativeTask> $tasks */
        $tasks = $process->tasks;
        /** @var Collection<int, AdministrativeDecision> $decisions */
        $decisions = $process->decisions;
        /** @var Collection<int, AdministrativeProcessNote> $notes */
        $notes = $process->notes;
        /** @var Collection<int, array<string, mixed>> $events */
        $events = collect();

        if ($application?->submitted_at) {
            $events->push([
                'date' => $application->submitted_at,
                'type' => 'application',
                'title' => 'Candidatura submetida',
                'description' => $application->application_number,
            ]);
        }

        foreach ($statusHistories as $history) {
            $events->push([
                'date' => $history->created_at,
                'type' => 'status',
                'title' => $this->label($history->to_status),
                'description' => $includeInternal ? $history->reason : null,
            ]);
        }

        foreach ($correctionRequests as $request) {
            if (! $includeInternal && ! $request->candidate_visible) {
                continue;
            }
            $events->push([
                'date' => $request->issued_at ?? $request->created_at,
                'type' => 'correction_request',
                'title' => 'Pedido de aperfeiçoamento '.$request->request_number,
                'description' => $request->subject,
            ]);

            /** @var Collection<int, CorrectionResponse> $responses */
            $responses = $request->responses;

            foreach ($responses as $response) {
                $events->push([
                    'date' => $response->submitted_at ?? $response->created_at,
                    'type' => 'correction_response',
                    'title' => 'Resposta ao aperfeiçoamento',
                    'description' => $this->label($response->status),
                ]);
            }
        }

        if ($includeInternal) {
            foreach ($reviews as $review) {
                $events->push([
                    'date' => $review->completed_at ?? $review->started_at ?? $review->created_at,
                    'type' => 'review',
                    'title' => $this->label($review->review_type),
                    'description' => $this->label($review->result) ?? $this->label($review->status),
                ]);
            }

            foreach ($tasks as $task) {
                $events->push([
                    'date' => $task->completed_at ?? $task->created_at,
                    'type' => 'task',
                    'title' => $task->title,
                    'description' => $this->label($task->status),
                ]);
            }
        }

        foreach ($decisions as $decision) {
            if (! $includeInternal && ! $decision->candidate_visible) {
                continue;
            }
            $events->push([
                'date' => $decision->approved_at ?? $decision->decided_at ?? $decision->created_at,
                'type' => 'decision',
                'title' => $this->label($decision->decision_type),
                'description' => $this->label($decision->decision_result),
            ]);
        }

        foreach ($notes as $note) {
            if (! $includeInternal && $this->noteVisibility($note->visibility) !== AdministrativeNoteVisibility::CandidateVisible) {
                continue;
            }
            $events->push([
                'date' => $note->created_at,
                'type' => 'note',
                'title' => $note->note_type,
                'description' => $note->body,
            ]);
        }

        return $events->sortByDesc('date')->values();
    }

    private function label(mixed $value): ?string
    {
        if (is_object($value) && method_exists($value, 'label')) {
            return $value->label();
        }

        return is_scalar($value) ? (string) $value : null;
    }

    private function noteVisibility(mixed $value): ?AdministrativeNoteVisibility
    {
        return $value instanceof AdministrativeNoteVisibility
            ? $value
            : AdministrativeNoteVisibility::tryFrom((string) $value);
    }
}
