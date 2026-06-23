<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdditionalDocumentSubmissionRequest;
use App\Models\AdditionalDocumentSubmission;
use App\Models\Application;
use App\Services\ApplicationActions\AdditionalDocumentSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AdditionalDocumentSubmissionController extends Controller
{
    public function __construct(private readonly AdditionalDocumentSubmissionService $service) {}

    public function create(Application $application): View
    {
        Gate::authorize('create', [AdditionalDocumentSubmission::class, $application]);
        $application->load(['additionalDocumentRequests']);

        return view('candidate.additional-documents.create', compact('application'));
    }

    public function store(StoreAdditionalDocumentSubmissionRequest $request, Application $application): RedirectResponse
    {
        Gate::authorize('create', [AdditionalDocumentSubmission::class, $application]);
        $this->service->submit(
            $application,
            $this->authenticatedUser($request),
            $request->validated(),
            $request->file('file'),
        );

        return to_route('candidate.applications.show', $application)->with('success', 'Documento adicional submetido.');
    }
}
