<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectDocumentSubmissionRequest;
use App\Http\Requests\ValidateDocumentSubmissionRequest;
use App\Models\DocumentSubmission;
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

    public function show(DocumentSubmission $documentSubmission): View
    {
        Gate::authorize('view', $documentSubmission);

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
        ]);

        return view('admin.document-reviews.show', ['submission' => $documentSubmission]);
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
        Gate::authorize('download', $documentSubmission);

        return $this->accessService->download($documentSubmission->load('currentVersion'), $this->authenticatedUser($request));
    }
}
