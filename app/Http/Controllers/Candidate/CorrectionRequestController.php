<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\CorrectionRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use App\Services\Administrative\CandidateCorrectionWorkspaceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CorrectionRequestController extends Controller
{
    public function __construct(
        private readonly CandidateCorrectionWorkspaceService $workspace,
    ) {}

    public function index(Request $request): View
    {
        $requests = CorrectionRequest::query()
            ->with([
                'application',
                'administrativeProcess',
                'items',
                'responses',
                'publicationResult',
            ])
            ->where('user_id', $this->authenticatedUser($request)->id)
            ->where('candidate_visible', true)
            ->where(function ($query): void {
                $query->whereHas('publicationResult', fn ($result) => $result
                    ->where('published_at', '<=', now()))
                    ->orWhere(function ($legacy): void {
                        $legacy
                            ->whereNull('application_review_publication_result_id')
                            ->whereNotNull('issued_at')
                            ->whereNotNull('notified_at')
                            ->whereNotNull('opened_at')
                            ->where(
                                'status',
                                '!=',
                                CorrectionRequestStatus::Cancelled->value,
                            )
                            ->whereHas('application', fn ($application) => $application
                                ->whereColumn(
                                    'applications.user_id',
                                    'correction_requests.user_id',
                                ))
                            ->whereHas('administrativeProcess', fn ($process) => $process
                                ->whereColumn(
                                    'administrative_processes.application_id',
                                    'correction_requests.application_id',
                                )
                                ->whereColumn(
                                    'administrative_processes.user_id',
                                    'correction_requests.user_id',
                                ));
                    });
            })
            ->latest()
            ->paginate(10);

        return view(
            'candidate.correction-requests.index',
            compact('requests'),
        );
    }

    public function show(CorrectionRequest $correctionRequest): View
    {
        Gate::authorize('view', $correctionRequest);

        $correctionRequest->load([
            'administrativeProcess',
            'application',
            'publicationResult',
            'items.documentType',
            'items.requiredDocument',
            'items.sourceDocumentSubmission.currentVersion',
            'items.responses.documentSubmission.currentVersion',
            'items.responses.documentVersion.replacedVersion',
            'responses.documentSubmission.currentVersion',
            'submissionReceipt',
            'deadlineExtensions.authorizedBy',
        ]);

        return view('candidate.correction-requests.show', [
            'correctionRequest' => $correctionRequest,
            'workspaceProgress' => $this->workspace->progress(
                $correctionRequest,
            ),
        ]);
    }
}
