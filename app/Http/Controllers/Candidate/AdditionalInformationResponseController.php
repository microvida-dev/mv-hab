<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitAdditionalInformationResponseRequest;
use App\Models\AdditionalInformationRequest;
use App\Models\DocumentSubmission;
use App\Services\Complaints\AdditionalInformationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AdditionalInformationResponseController extends Controller
{
    public function __construct(private readonly AdditionalInformationService $service) {}

    public function show(AdditionalInformationRequest $additionalInformationRequest): View
    {
        Gate::authorize('view', $additionalInformationRequest);
        $additionalInformationRequest->load(['complaint', 'responses.documentSubmission']);

        return view('candidate.additional-information.show', compact('additionalInformationRequest'));
    }

    public function create(AdditionalInformationRequest $additionalInformationRequest): View
    {
        Gate::authorize('view', $additionalInformationRequest);
        $documents = DocumentSubmission::query()->where('user_id', auth()->id())->latest()->get();

        return view('candidate.additional-information.respond', compact('additionalInformationRequest', 'documents'));
    }

    public function store(SubmitAdditionalInformationResponseRequest $request, AdditionalInformationRequest $additionalInformationRequest): RedirectResponse
    {
        Gate::authorize('view', $additionalInformationRequest);
        $this->service->respond($additionalInformationRequest, $request->validated(), $this->authenticatedUser($request));

        return to_route('candidate.additional-information.show', $additionalInformationRequest)->with('success', 'Resposta complementar submetida.');
    }
}
