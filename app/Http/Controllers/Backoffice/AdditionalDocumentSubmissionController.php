<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AdditionalDocumentSubmission;
use App\Services\ApplicationActions\AdditionalDocumentSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdditionalDocumentSubmissionController extends Controller
{
    public function __construct(private readonly AdditionalDocumentSubmissionService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', AdditionalDocumentSubmission::class);

        return view('backoffice.additional-document-submissions.index', [
            'submissions' => AdditionalDocumentSubmission::query()->with(['application', 'user'])->latest()->paginate(20),
        ]);
    }

    public function show(AdditionalDocumentSubmission $additionalDocumentSubmission): View
    {
        Gate::authorize('view', $additionalDocumentSubmission);

        return view('backoffice.additional-document-submissions.show', ['submission' => $additionalDocumentSubmission]);
    }

    public function decide(Request $request, AdditionalDocumentSubmission $additionalDocumentSubmission): RedirectResponse
    {
        Gate::authorize('update', $additionalDocumentSubmission);
        $data = $request->validate([
            'accepted' => ['required', 'boolean'],
            'rejection_reason' => ['nullable', 'required_if:accepted,0', 'string', 'max:2000'],
        ]);
        $this->service->decide($additionalDocumentSubmission, $this->authenticatedUser($request), (bool) $data['accepted'], $data['rejection_reason'] ?? null);

        return back()->with('success', 'Submissão analisada.');
    }
}
