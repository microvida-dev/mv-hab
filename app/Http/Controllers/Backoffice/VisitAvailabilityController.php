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
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Visits\VisitAvailabilityService;
use App\Services\Visits\VisitSlotGenerationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VisitAvailabilityController extends Controller
{
    public function __construct(
        private readonly VisitAvailabilityService $availabilities,
        private readonly VisitSlotGenerationService $slots,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize(
            'viewAnyBackoffice',
            VisitAvailability::class,
        );
        $actor = $this->authenticatedUser($request);

        return view('backoffice.visit-availabilities.index', [
            'availabilities' => $this->municipalScope
                ->visitAvailabilities(
                    VisitAvailability::query(),
                    $actor,
                )
                ->with(['contest', 'housingUnit', 'staff'])
                ->latest('starts_at')
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize(
            'createBackoffice',
            VisitAvailability::class,
        );

        return view(
            'backoffice.visit-availabilities.create',
            $this->formData($this->authenticatedUser($request)),
        );
    }

    public function store(StoreVisitAvailabilityRequest $request): RedirectResponse
    {
        $availability = $this->availabilities->store($request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.visit-availabilities.show', $availability)->with('success', 'Disponibilidade criada.');
    }

    public function show(VisitAvailability $visitAvailability): View
    {
        Gate::authorize('viewBackoffice', $visitAvailability);
        $visitAvailability->load(['contest', 'housingUnit', 'staff', 'slots.visits']);

        return view('backoffice.visit-availabilities.show', ['availability' => $visitAvailability]);
    }

    public function edit(
        Request $request,
        VisitAvailability $visitAvailability,
    ): View {
        Gate::authorize('updateBackoffice', $visitAvailability);

        return view('backoffice.visit-availabilities.edit', [
            'availability' => $visitAvailability,
            ...$this->formData(
                $this->authenticatedUser($request),
            ),
        ]);
    }

    public function update(UpdateVisitAvailabilityRequest $request, VisitAvailability $visitAvailability): RedirectResponse
    {
        $availability = $this->availabilities->update($visitAvailability, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.visit-availabilities.show', $availability)->with('success', 'Disponibilidade atualizada.');
    }

    public function destroy(VisitAvailability $visitAvailability): RedirectResponse
    {
        Gate::authorize('deleteBackoffice', $visitAvailability);
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
    private function formData(User $actor): array
    {
        return [
            'contests' => $this->municipalScope
                ->contests(Contest::query(), $actor)
                ->whereHas('program', fn (Builder $query): Builder => $query
                    ->whereNotNull('municipality_id'))
                ->orderBy('title')
                ->get(['id', 'code', 'title', 'program_id']),
            'housingUnits' => $this->municipalScope
                ->housingUnits(HousingUnit::query(), $actor)
                ->whereNotNull('municipality_id')
                ->orderBy('code')
                ->get(['id', 'code', 'municipality_id']),
            'staffUsers' => $this->municipalScope
                ->users(User::query(), $actor)
                ->whereNotNull('municipality_id')
                ->orderBy('name')
                ->get(['id', 'name', 'municipality_id']),
        ];
    }
}
