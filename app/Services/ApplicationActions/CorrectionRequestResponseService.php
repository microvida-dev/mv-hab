<?php

namespace App\Services\ApplicationActions;

use App\Enums\TimelineEventType;
use App\Enums\TimelineEventVisibility;
use App\Models\Application;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\User;
use App\Services\Administrative\CorrectionResponseService as ExistingCorrectionResponseService;
use App\Services\ProcessTracking\ProcessTimelineService;
use Illuminate\Validation\ValidationException;

class CorrectionRequestResponseService
{
    public function __construct(
        private readonly ExistingCorrectionResponseService $responses,
        private readonly ProcessTimelineService $timeline,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(CorrectionRequest $request, array $data, User $candidate): CorrectionResponse
    {
        $response = $this->responses->submit($request, $data, $candidate);
        $application = $request->application;

        if (! $application instanceof Application) {
            throw ValidationException::withMessages(['application' => 'Pedido sem candidatura associada.']);
        }

        $this->timeline->record(
            application: $application,
            type: TimelineEventType::CorrectionSubmitted,
            visibility: TimelineEventVisibility::CandidateVisible,
            title: 'Resposta ao aperfeiçoamento submetida',
            description: $request->subject,
            actor: $candidate,
            related: $response,
        );

        return $response;
    }
}
