<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptAllocationOfferRequest;
use App\Http\Requests\RefuseAllocationOfferRequest;
use App\Models\AllocationOffer;
use App\Services\Allocation\AllocationResponseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AllocationResponseController extends Controller
{
    public function __construct(private readonly AllocationResponseService $responseService) {}

    public function accept(AcceptAllocationOfferRequest $request, AllocationOffer $allocationOffer): RedirectResponse
    {
        Gate::authorize('respond', $allocationOffer);
        $this->responseService->accept($allocationOffer, $this->authenticatedUser($request), $request->validated('candidate_response'));

        return to_route('candidate.allocation-offers.show', $allocationOffer)->with('success', 'Oferta aceite. A candidatura fica pronta para contrato.');
    }

    public function refuse(RefuseAllocationOfferRequest $request, AllocationOffer $allocationOffer): RedirectResponse
    {
        Gate::authorize('respond', $allocationOffer);
        $this->responseService->refuse($allocationOffer, $this->authenticatedUser($request), $request->validated('refusal_reason'));

        return to_route('candidate.allocation-offers.show', $allocationOffer)->with('success', 'Oferta recusada.');
    }
}
