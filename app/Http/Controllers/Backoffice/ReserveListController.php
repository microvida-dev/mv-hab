<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\CallNextReserveCandidateRequest;
use App\Models\Allocation;
use App\Models\ReserveList;
use App\Services\Allocation\ReplacementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReserveListController extends Controller
{
    public function __construct(private readonly ReplacementService $replacementService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ReserveList::class);

        return view('backoffice.allocation.reserve-lists.index', [
            'reserveLists' => ReserveList::query()->with(['contest', 'allocationRun'])->latest()->paginate(15),
        ]);
    }

    public function show(ReserveList $reserveList): View
    {
        Gate::authorize('view', $reserveList);
        $reserveList->load(['allocationRun', 'contest', 'entries.candidate', 'entries.application']);

        return view('backoffice.allocation.reserve-lists.show', compact('reserveList'));
    }

    public function callNext(CallNextReserveCandidateRequest $request, ReserveList $reserveList): RedirectResponse
    {
        Gate::authorize('update', $reserveList);

        $validated = $request->validated();

        $allocation = Allocation::query()
            ->findOrFail((int) $validated['replacement_for_allocation_id']);

        $this->replacementService->callNextFor(
            $allocation,
            $this->authenticatedUser($request),
        );

        return back()->with('success', 'Próximo candidato suplente chamado.');
    }
}
