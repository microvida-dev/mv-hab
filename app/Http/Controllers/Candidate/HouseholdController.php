<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCandidateHouseholdRequest;
use App\Http\Requests\UpdateCandidateHouseholdRequest;
use App\Models\AdhesionRegistration;
use App\Models\Household;
use App\Services\Candidate\HouseholdService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HouseholdController extends Controller
{
    public function __construct(private readonly HouseholdService $householdService) {}

    public function show(Request $request): View|RedirectResponse
    {
        $registration = $this->authenticatedUser($request)->adhesionRegistration;

        if (! $registration instanceof AdhesionRegistration) {
            return to_route('candidate.registration.create')
                ->with('info', 'Inicie o Registo de Adesão antes de preencher o agregado.');
        }

        $household = $registration->household()
            ->with(['members.incomeRecords', 'incomeRecords'])
            ->first();

        if ($household !== null) {
            Gate::authorize('view', $household);
        }

        return view('candidate.household.show', compact('registration', 'household'));
    }

    public function store(StoreCandidateHouseholdRequest $request): RedirectResponse
    {
        $registration = $this->authenticatedUser($request)->adhesionRegistration;
        abort_unless($registration instanceof AdhesionRegistration, 404);

        $this->householdService->create(
            $registration,
            $request->validated(),
            $this->authenticatedUser($request),
        );

        return to_route('candidate.household.show')
            ->with('success', 'Agregado criado e requerente sincronizado com o Registo de Adesão.');
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $registration = $this->authenticatedUser($request)->adhesionRegistration()->first();

        if (! $registration instanceof AdhesionRegistration) {
            return to_route('candidate.registration.create');
        }

        $household = $registration->household()->first();

        if (! $household instanceof Household) {
            return to_route('candidate.household.show');
        }

        Gate::authorize('update', $household);

        return view('candidate.household.edit', compact('household'));
    }

    public function update(UpdateCandidateHouseholdRequest $request): RedirectResponse
    {
        $registration = $this->authenticatedUser($request)->adhesionRegistration()->firstOrFail();

        $household = $registration->household()->firstOrFail();

        $this->householdService->update($household, $request->validated(), $this->authenticatedUser($request));

        return to_route('candidate.household.show')
            ->with('success', 'Dados gerais do agregado atualizados.');
    }
}
