<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContestHousingUnitRequest;
use App\Http\Requests\UpdateContestHousingUnitRequest;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use App\Models\Program;
use App\Services\Allocation\ContestHousingUnitService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContestHousingUnitController extends Controller
{
    public function __construct(private readonly ContestHousingUnitService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ContestHousingUnit::class);

        return view('backoffice.allocation.contest-housing-units.index', [
            'units' => ContestHousingUnit::query()->with(['contest', 'housingUnit'])->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', ContestHousingUnit::class);

        return view('backoffice.allocation.contest-housing-units.create', $this->formData());
    }

    public function store(StoreContestHousingUnitRequest $request): RedirectResponse
    {
        Gate::authorize('create', ContestHousingUnit::class);
        $unit = $this->service->create($request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.allocation.contest-housing-units.show', $unit)->with('success', 'Habitação associada ao concurso.');
    }

    public function show(ContestHousingUnit $contestHousingUnit): View
    {
        Gate::authorize('view', $contestHousingUnit);
        $contestHousingUnit->load(['contest', 'program', 'housingUnit', 'allocations.candidate']);

        return view('backoffice.allocation.contest-housing-units.show', compact('contestHousingUnit'));
    }

    public function edit(ContestHousingUnit $contestHousingUnit): View
    {
        Gate::authorize('update', $contestHousingUnit);

        return view('backoffice.allocation.contest-housing-units.edit', $this->formData() + compact('contestHousingUnit'));
    }

    public function update(UpdateContestHousingUnitRequest $request, ContestHousingUnit $contestHousingUnit): RedirectResponse
    {
        Gate::authorize('update', $contestHousingUnit);
        $this->service->update($contestHousingUnit, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.allocation.contest-housing-units.show', $contestHousingUnit)->with('success', 'Habitação atualizada.');
    }

    public function destroy(Request $request, ContestHousingUnit $contestHousingUnit): RedirectResponse
    {
        Gate::authorize('delete', $contestHousingUnit);
        $this->service->remove($contestHousingUnit, $this->authenticatedUser($request));

        return to_route('backoffice.allocation.contest-housing-units.index')->with('success', 'Habitação removida.');
    }

    public function markAvailable(Request $request, ContestHousingUnit $contestHousingUnit): RedirectResponse
    {
        Gate::authorize('update', $contestHousingUnit);
        $this->service->markAvailable($contestHousingUnit, $this->authenticatedUser($request));

        return back()->with('success', 'Habitação marcada como disponível.');
    }

    public function markUnavailable(Request $request, ContestHousingUnit $contestHousingUnit): RedirectResponse
    {
        Gate::authorize('update', $contestHousingUnit);
        $this->service->markUnavailable($contestHousingUnit, $this->authenticatedUser($request));

        return back()->with('success', 'Habitação marcada como indisponível.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'programs' => Program::query()->orderBy('name')->get(),
            'contests' => Contest::query()->orderByDesc('created_at')->get(),
            'housingUnits' => HousingUnit::query()->orderBy('code')->get(),
        ];
    }
}
