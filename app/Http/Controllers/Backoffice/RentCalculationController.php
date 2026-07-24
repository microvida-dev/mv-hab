<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveRentCalculationRequest;
use App\Http\Requests\CalculateRentRequest;
use App\Http\Requests\RejectRentCalculationRequest;
use App\Models\Allocation;
use App\Models\RentCalculation;
use App\Models\RentRuleSet;
use App\Services\Contracts\RentCalculationService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RentCalculationController extends Controller
{
    public function __construct(
        private readonly RentCalculationService $service,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', RentCalculation::class);
        $actor = $this->authenticatedUser($request);

        return view('backoffice.contracts.rent-calculations.index', [
            'calculations' => $this->municipalScope
                ->rentCalculations(RentCalculation::query(), $actor)
                ->with(['candidate', 'allocation', 'housingUnit', 'rentRuleSet'])
                ->latest()
                ->paginate(20),
            'allocations' => $this->municipalScope
                ->allocations(Allocation::query(), $actor)
                ->readyForContract()
                ->with(['candidate', 'housingUnit'])
                ->get(),
            'ruleSets' => $this->municipalScope
                ->rentRuleSets(RentRuleSet::query(), $actor)
                ->active()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(RentCalculation $rentCalculation): View
    {
        Gate::authorize('viewBackoffice', $rentCalculation);
        $rentCalculation->load(['candidate', 'application', 'allocation', 'housingUnit', 'rentRuleSet', 'details', 'manualReviews']);

        return view('backoffice.contracts.rent-calculations.show', compact('rentCalculation'));
    }

    public function calculate(CalculateRentRequest $request): RedirectResponse
    {
        Gate::authorize('calculateBackoffice', RentCalculation::class);

        $validated = $request->validated();
        $actor = $this->authenticatedUser($request);

        $allocation = $this->municipalScope
            ->allocations(Allocation::query(), $actor)
            ->with('application')
            ->findOrFail((int) $validated['allocation_id']);

        $ruleSet = filled($validated['rent_rule_set_id'] ?? null)
            ? $this->municipalScope
                ->rentRuleSets(RentRuleSet::query(), $actor)
                ->findOrFail((int) $validated['rent_rule_set_id'])
            : null;

        $calculation = $this->service->calculate(
            $allocation,
            $actor,
            $ruleSet,
            $validated['notes'] ?? null,
        );

        return to_route('backoffice.contracts.rent-calculations.show', $calculation)
            ->with('success', 'Cálculo de renda criado.');
    }

    public function approve(ApproveRentCalculationRequest $request, RentCalculation $rentCalculation): RedirectResponse
    {
        Gate::authorize('approveBackoffice', $rentCalculation);
        $this->service->approve($rentCalculation, $this->authenticatedUser($request), $request->validated('notes'));

        return back()->with('success', 'Cálculo aprovado.');
    }

    public function reject(RejectRentCalculationRequest $request, RentCalculation $rentCalculation): RedirectResponse
    {
        Gate::authorize('rejectBackoffice', $rentCalculation);
        $this->service->reject($rentCalculation, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Cálculo rejeitado.');
    }

    public function recalculate(Request $request, RentCalculation $rentCalculation): RedirectResponse
    {
        Gate::authorize('recalculateBackoffice', $rentCalculation);
        $allocation = $rentCalculation->allocation;
        $ruleSet = $rentCalculation->rentRuleSet;
        abort_unless($allocation instanceof Allocation, 500);

        $calculation = $this->service->calculate($allocation, $this->authenticatedUser($request), $ruleSet, $request->input('notes'));

        return to_route('backoffice.contracts.rent-calculations.show', $calculation)->with('success', 'Cálculo recalculado.');
    }
}
