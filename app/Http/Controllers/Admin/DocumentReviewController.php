<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentAccessAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectDocumentSubmissionRequest;
use App\Http\Requests\ValidateDocumentSubmissionRequest;
use App\Models\DocumentSubmission;
use App\Services\DocumentIntelligence\DocumentAiManualAnalysisService;
use App\Services\Documents\DocumentAccessService;
use App\Services\Documents\DocumentReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentReviewController extends Controller
{
    public function __construct(
        private readonly DocumentReviewService $reviewService,
        private readonly DocumentAccessService $accessService,
        private readonly DocumentAiManualAnalysisService $documentAiManualAnalysis,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', DocumentSubmission::class);

        $submissions = DocumentSubmission::query()
            ->with(['documentType', 'adhesionRegistration.user', 'householdMember', 'incomeRecord.incomeSource', 'currentHousingSituation'])
            ->latest()
            ->paginate(15);

        return view('admin.document-reviews.index', compact('submissions'));
    }

    public function show(Request $request, DocumentSubmission $documentSubmission): View
    {
        if (Gate::denies('view', $documentSubmission)) {
            $this->accessService->denied($documentSubmission, $this->authenticatedUser($request), 'view');
            abort(403);
        }

        $documentSubmission->load([
            'documentType',
            'requiredDocument',
            'adhesionRegistration.user',
            'householdMember',
            'incomeRecord.incomeSource',
            'currentHousingSituation',
            'versions.uploadedBy',
            'reviews.reviewedBy',
            'accessLogs.user',
            'latestDocumentAiAnalysis.latestScore',
        ]);
        $this->accessService->record($documentSubmission, DocumentAccessAction::View, $documentSubmission->currentVersion, $this->authenticatedUser($request));

        return view('admin.document-reviews.show', ['submission' => $documentSubmission]);
    }

    public function runDocumentAi(Request $request, DocumentSubmission $documentSubmission): RedirectResponse
    {
        Gate::authorize('review', $documentSubmission);

        $analysis = $this->documentAiManualAnalysis->execute(
            $documentSubmission,
            $this->authenticatedUser($request),
        );

        return to_route('backoffice.document-ai.assistant.show', $analysis)
            ->with('success', 'Análise IA documental executada. A decisão final continua a exigir revisão técnica.');
    }

    public function underReview(
        ValidateDocumentSubmissionRequest $request,
        DocumentSubmission $documentSubmission,
    ): RedirectResponse {
        $submission = $this->reviewService->markUnderReview(
            $documentSubmission,
            $this->authenticatedUser($request),
            $request->validated('internal_notes'),
        );

        return to_route('admin.document-reviews.show', $submission)
            ->with('success', 'Documento colocado em análise.');
    }

    public function validateDocument(
        ValidateDocumentSubmissionRequest $request,
        DocumentSubmission $documentSubmission,
    ): RedirectResponse {
        $submission = $this->reviewService->validate(
            $documentSubmission,
            $this->authenticatedUser($request),
            $request->validated('internal_notes'),
        );

        return to_route('admin.document-reviews.show', $submission)
            ->with('success', 'Documento validado.');
    }

    public function reject(
        RejectDocumentSubmissionRequest $request,
        DocumentSubmission $documentSubmission,
    ): RedirectResponse {
        $submission = $this->reviewService->reject(
            $documentSubmission,
            $this->authenticatedUser($request),
            $request->validated('rejection_reason'),
            $request->validated('internal_notes'),
        );

        return to_route('admin.document-reviews.show', $submission)
            ->with('success', 'Documento rejeitado.');
    }

    public function download(Request $request, DocumentSubmission $documentSubmission): StreamedResponse
    {
        if (Gate::denies('download', $documentSubmission)) {
            $this->accessService->denied($documentSubmission, $this->authenticatedUser($request), 'download');
            abort(403);
        }

        return $this->accessService->download($documentSubmission->load('currentVersion'), $this->authenticatedUser($request));
    }
}
