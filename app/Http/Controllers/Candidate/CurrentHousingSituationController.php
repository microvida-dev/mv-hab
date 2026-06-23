<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\HousingCondition;
use App\Enums\HousingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCurrentHousingSituationRequest;
use App\Models\AdhesionRegistration;
use App\Models\Household;
use App\Services\Candidate\HousingSituationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CurrentHousingSituationController extends Controller
{
    public function __construct(private readonly HousingSituationService $housingService) {}

    public function show(Request $request): View|RedirectResponse
    {
        $registration = $this->authenticatedUser($request)->adhesionRegistration;

        if (! $registration instanceof AdhesionRegistration) {
            return to_route('candidate.registration.create');
        }

        $situation = $registration->currentHousingSituation;

        if ($situation !== null) {
            Gate::authorize('view', $situation);
        }

        $household = $registration->household;

        $monthlyIncome = $household instanceof Household ? (float) $household->monthly_income : 0.0;
        $effortRate = $situation?->effortRate($monthlyIncome);

        return view('candidate.current-housing.show', compact(
            'registration',
            'situation',
            'monthlyIncome',
            'effortRate',
        ));
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $registration = $this->authenticatedUser($request)->adhesionRegistration;

        if (! $registration instanceof AdhesionRegistration) {
            return to_route('candidate.registration.create');
        }

        Gate::authorize('update', $registration);
        $situation = $registration->currentHousingSituation;

        return view('candidate.current-housing.edit', [
            'situation' => $situation,
            'housingStatuses' => HousingStatus::options(),
            'housingConditions' => HousingCondition::options(),
        ]);
    }

    public function update(UpdateCurrentHousingSituationRequest $request): RedirectResponse
    {
        $registration = $this->authenticatedUser($request)->adhesionRegistration;
        abort_unless($registration instanceof AdhesionRegistration, 404);

        $this->housingService->updateOrCreate(
            $registration,
            $request->validated(),
            $this->authenticatedUser($request),
        );

        return to_route('candidate.current-housing.show')
            ->with('success', 'Situação habitacional atual guardada.');
    }
}
