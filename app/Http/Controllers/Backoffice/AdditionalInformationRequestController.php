<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdditionalInformationRequestRequest;
use App\Models\AdditionalInformationRequest;
use App\Models\Complaint;
use App\Services\Complaints\AdditionalInformationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdditionalInformationRequestController extends Controller
{
    public function __construct(private readonly AdditionalInformationService $service) {}

    public function create(Complaint $complaint): View
    {
        Gate::authorize('create', AdditionalInformationRequest::class);

        return view('backoffice.additional-information-requests.create', compact('complaint'));
    }

    public function store(StoreAdditionalInformationRequestRequest $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('create', AdditionalInformationRequest::class);
        $informationRequest = $this->service->create($complaint, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.additional-information-requests.show', $informationRequest)->with('success', 'Pedido complementar emitido.');
    }

    public function show(AdditionalInformationRequest $additionalInformationRequest): View
    {
        Gate::authorize('view', $additionalInformationRequest);
        $additionalInformationRequest->load(['complaint', 'application', 'candidate', 'issuedBy', 'responses.documentSubmission']);

        return view('backoffice.additional-information-requests.show', compact('additionalInformationRequest'));
    }

    public function close(Request $request, AdditionalInformationRequest $additionalInformationRequest): RedirectResponse
    {
        Gate::authorize('update', $additionalInformationRequest);
        $this->service->close($additionalInformationRequest, $this->authenticatedUser($request));

        return back()->with('success', 'Pedido complementar fechado.');
    }

    public function markOverdue(Request $request, AdditionalInformationRequest $additionalInformationRequest): RedirectResponse
    {
        Gate::authorize('update', $additionalInformationRequest);
        $this->service->markOverdue($additionalInformationRequest, $this->authenticatedUser($request));

        return back()->with('success', 'Pedido complementar marcado como vencido.');
    }
}
