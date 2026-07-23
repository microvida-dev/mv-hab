<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHouseholdRequest;
use App\Http\Requests\UpdateHouseholdRequest;
use App\Models\Citizen;
use App\Models\Household;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HouseholdController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', Household::class);
        $households = $this->municipalScope->households(
            Household::query(),
            $this->authenticatedUser($request),
        )
            ->with(['citizen', 'adhesionRegistration'])
            ->withCount('housingApplications')
            ->latest()
            ->paginate(15);

        return view('households.index', compact('households'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('createBackoffice', Household::class);
        $citizens = $this->municipalScope->citizens(
            Citizen::query(),
            $this->authenticatedUser($request),
        )
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('households.create', compact('citizens'));
    }

    public function store(StoreHouseholdRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', Household::class);
        $household = new Household($request->validated());
        $household->forceFill([
            'municipality_id' => $this->authenticatedUser($request)->municipality_id,
        ])->save();
        $this->auditLogger->record(
            AuditEvents::CREATE,
            $household,
            'households',
            'create',
            'Agregado familiar criado no âmbito municipal.',
        );

        return to_route('households.index')
            ->with('success', 'Agregado familiar criado com sucesso.');
    }

    public function show(Request $request, Household $household): View
    {
        Gate::authorize('viewBackoffice', $household);
        $household->load(['citizen', 'adhesionRegistration', 'housingApplications.citizen']);

        return view('households.show', compact('household'));
    }

    public function edit(Request $request, Household $household): View
    {
        Gate::authorize('updateBackoffice', $household);
        $citizens = $this->municipalScope->citizens(
            Citizen::query(),
            $this->authenticatedUser($request),
        )
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('households.edit', compact('household', 'citizens'));
    }

    public function update(UpdateHouseholdRequest $request, Household $household): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $household);
        $household->update($request->validated());
        $this->auditLogger->record(
            AuditEvents::UPDATE,
            $household,
            'households',
            'update',
            'Agregado familiar atualizado no âmbito municipal.',
        );

        return to_route('households.index')
            ->with('success', 'Agregado familiar atualizado com sucesso.');
    }

    public function destroy(Request $request, Household $household): RedirectResponse
    {
        Gate::authorize('deleteBackoffice', $household);
        $household->delete();
        $this->auditLogger->record(
            AuditEvents::DELETE,
            $household,
            'households',
            'delete',
            'Agregado familiar removido no âmbito municipal.',
        );

        return to_route('households.index')
            ->with('success', 'Agregado familiar eliminado com sucesso.');
    }
}
