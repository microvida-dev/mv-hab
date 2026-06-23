<?php

namespace App\Http\Controllers\Candidate\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitAnnualDocumentUpdateRequestRequest;
use App\Models\AnnualDocumentUpdateRequest;
use App\Models\DocumentSubmission;
use App\Services\Finance\AnnualDocumentUpdateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AnnualDocumentUpdateRequestController extends Controller
{
    public function __construct(private readonly AnnualDocumentUpdateService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', AnnualDocumentUpdateRequest::class);
        $requests = AnnualDocumentUpdateRequest::query()->where('user_id', $this->currentUser()->id)->latest()->paginate(20);

        return view('candidate.finance.annual-document-updates.index', compact('requests'));
    }

    public function show(AnnualDocumentUpdateRequest $annualDocumentUpdateRequest): View
    {
        Gate::authorize('view', $annualDocumentUpdateRequest);
        $annualDocumentUpdateRequest->load('submissions.documentSubmission');
        $documents = DocumentSubmission::query()->where('user_id', $this->currentUser()->id)->latest()->get();

        return view('candidate.finance.annual-document-updates.show', compact('annualDocumentUpdateRequest', 'documents'));
    }

    public function submit(SubmitAnnualDocumentUpdateRequestRequest $request, AnnualDocumentUpdateRequest $annualDocumentUpdateRequest): RedirectResponse
    {
        Gate::authorize('update', $annualDocumentUpdateRequest);
        $this->service->submit($annualDocumentUpdateRequest, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Documentos submetidos.');
    }
}
