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
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RentCalculationController extends Controller
{
    public function __construct(private readonly RentCalculationService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', RentCalculation::class);

        return view('backoffice.contracts.rent-calculations.index', [
            'calculations' => RentCalculation::query()->with(['candidate', 'allocation', 'housingUnit', 'rentRuleSet'])->latest()->paginate(20),
            'allocations' => Allocation::query()->readyForContract()->with(['candidate', 'housingUnit'])->get(),
            'ruleSets' => RentRuleSet::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function show(RentCalculation $rentCalculation): View
    {
        Gate::authorize('view', $rentCalculation);
        $rentCalculation->load(['candidate', 'application', 'allocation', 'housingUnit', 'rentRuleSet', 'details', 'manualReviews']);

        return view('backoffice.contracts.rent-calculations.show', compact('rentCalculation'));
    }

    public function calculate(CalculateRentRequest $request): RedirectResponse
    {
        Gate::authorize('create', RentCalculation::class);

        $validated = $request->validated();

        $allocation = Allocation::query()
            ->with('application')
            ->findOrFail((int) $validated['allocation_id']);

        $ruleSet = filled($validated['rent_rule_set_id'] ?? null)
            ? RentRuleSet::query()->findOrFail((int) $validated['rent_rule_set_id'])
            : null;

        $calculation = $this->service->calculate(
            $allocation,
            $this->authenticatedUser($request),
            $ruleSet,
            $validated['notes'] ?? null,
        );

        return to_route('backoffice.contracts.rent-calculations.show', $calculation)
            ->with('success', 'Cálculo de renda criado.');
    }

    public function approve(ApproveRentCalculationRequest $request, RentCalculation $rentCalculation): RedirectResponse
    {
        Gate::authorize('approve', $rentCalculation);
        $this->service->approve($rentCalculation, $this->authenticatedUser($request), $request->validated('notes'));

        return back()->with('success', 'Cálculo aprovado.');
    }

    public function reject(RejectRentCalculationRequest $request, RentCalculation $rentCalculation): RedirectResponse
    {
        Gate::authorize('approve', $rentCalculation);
        $this->service->reject($rentCalculation, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Cálculo rejeitado.');
    }

    public function recalculate(Request $request, RentCalculation $rentCalculation): RedirectResponse
    {
        Gate::authorize('update', $rentCalculation);
        $allocation = $rentCalculation->allocation;
        $ruleSet = $rentCalculation->rentRuleSet;
        abort_unless($allocation instanceof Allocation, 500);

        $calculation = $this->service->calculate($allocation, $this->authenticatedUser($request), $ruleSet, $request->input('notes'));

        return to_route('backoffice.contracts.rent-calculations.show', $calculation)->with('success', 'Cálculo recalculado.');
    }
}
