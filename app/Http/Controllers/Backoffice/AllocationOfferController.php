<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\IssueAllocationOfferRequest;
use App\Models\AllocationOffer;
use App\Services\Allocation\AllocationOfferService;
use App\Services\Allocation\AllocationResponseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AllocationOfferController extends Controller
{
    public function __construct(
        private readonly AllocationOfferService $offerService,
        private readonly AllocationResponseService $responseService,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', AllocationOffer::class);

        return view('backoffice.allocation.offers.index', [
            'offers' => AllocationOffer::query()
                ->with(['candidate', 'housingUnit', 'allocation'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(AllocationOffer $allocationOffer): View
    {
        Gate::authorize('view', $allocationOffer);
        $allocationOffer->load(['candidate', 'allocation.contest', 'allocation.program', 'housingUnit', 'contestHousingUnit']);

        return view('backoffice.allocation.offers.show', compact('allocationOffer'));
    }

    public function issue(IssueAllocationOfferRequest $request, AllocationOffer $allocationOffer): RedirectResponse
    {
        Gate::authorize('update', $allocationOffer);
        $this->offerService->issue($allocationOffer, $this->authenticatedUser($request));

        return back()->with('success', 'Oferta emitida.');
    }

    public function markExpired(IssueAllocationOfferRequest $request, AllocationOffer $allocationOffer): RedirectResponse
    {
        Gate::authorize('update', $allocationOffer);
        $this->responseService->expire($allocationOffer, $this->authenticatedUser($request));

        return back()->with('success', 'Oferta marcada como expirada.');
    }
}
