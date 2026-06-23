<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\WithdrawAllocationRequest;
use App\Models\Allocation;
use App\Services\Allocation\AllocationResponseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AllocationController extends Controller
{
    public function __construct(private readonly AllocationResponseService $responseService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Allocation::class);

        return view('candidate.allocations.index', [
            'allocations' => Allocation::query()
                ->where('user_id', $this->currentUser()->id)
                ->with(['contest', 'housingUnit', 'activeOffer'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(Allocation $allocation): View
    {
        Gate::authorize('view', $allocation);
        $allocation->load(['contest', 'housingUnit', 'offers', 'allocationRun']);

        return view('candidate.allocations.show', compact('allocation'));
    }

    public function withdraw(WithdrawAllocationRequest $request, Allocation $allocation): RedirectResponse
    {
        Gate::authorize('withdraw', $allocation);
        $this->responseService->withdraw($allocation, $this->authenticatedUser($request), $request->validated('withdrawal_reason'));

        return to_route('candidate.allocations.show', $allocation)->with('success', 'Desistência registada.');
    }
}
