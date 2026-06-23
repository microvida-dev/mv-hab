<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateVisitSlotsRequest;
use App\Http\Requests\StoreVisitAvailabilityRequest;
use App\Http\Requests\UpdateVisitAvailabilityRequest;
use App\Models\Contest;
use App\Models\HousingUnit;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Services\Visits\VisitAvailabilityService;
use App\Services\Visits\VisitSlotGenerationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class VisitAvailabilityController extends Controller
{
    public function __construct(
        private readonly VisitAvailabilityService $availabilities,
        private readonly VisitSlotGenerationService $slots,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', VisitAvailability::class);

        return view('backoffice.visit-availabilities.index', [
            'availabilities' => VisitAvailability::query()->with(['contest', 'housingUnit', 'staff'])->latest('starts_at')->paginate(15),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', VisitAvailability::class);

        return view('backoffice.visit-availabilities.create', $this->formData());
    }

    public function store(StoreVisitAvailabilityRequest $request): RedirectResponse
    {
        $availability = $this->availabilities->store($request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.visit-availabilities.show', $availability)->with('success', 'Disponibilidade criada.');
    }

    public function show(VisitAvailability $visitAvailability): View
    {
        Gate::authorize('view', $visitAvailability);
        $visitAvailability->load(['contest', 'housingUnit', 'staff', 'slots.visits']);

        return view('backoffice.visit-availabilities.show', ['availability' => $visitAvailability]);
    }

    public function edit(VisitAvailability $visitAvailability): View
    {
        Gate::authorize('update', $visitAvailability);

        return view('backoffice.visit-availabilities.edit', [
            'availability' => $visitAvailability,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateVisitAvailabilityRequest $request, VisitAvailability $visitAvailability): RedirectResponse
    {
        $availability = $this->availabilities->update($visitAvailability, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.visit-availabilities.show', $availability)->with('success', 'Disponibilidade atualizada.');
    }

    public function destroy(VisitAvailability $visitAvailability): RedirectResponse
    {
        Gate::authorize('delete', $visitAvailability);
        $visitAvailability->delete();

        return to_route('backoffice.visit-availabilities.index')->with('success', 'Disponibilidade removida.');
    }

    public function generateSlots(GenerateVisitSlotsRequest $request, VisitAvailability $visitAvailability): RedirectResponse
    {
        $slots = $this->slots->generate($visitAvailability, $this->authenticatedUser($request), $request->validated());

        return to_route('backoffice.visit-availabilities.show', $visitAvailability)->with('success', $slots->count().' slots gerados.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'contests' => Contest::query()->orderBy('title')->get(),
            'housingUnits' => HousingUnit::query()->orderBy('code')->get(),
            'staffUsers' => User::query()->orderBy('name')->get(),
        ];
    }
}
