<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\AllocationOffer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class AllocationOfferController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', AllocationOffer::class);

        return view('candidate.allocation-offers.index', [
            'offers' => AllocationOffer::query()
                ->where('user_id', $this->currentUser()->id)
                ->with(['allocation', 'housingUnit'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(AllocationOffer $allocationOffer): View
    {
        Gate::authorize('view', $allocationOffer);
        $allocationOffer->load(['allocation.contest', 'allocation.program', 'housingUnit']);

        return view('candidate.allocation-offers.show', compact('allocationOffer'));
    }
}
