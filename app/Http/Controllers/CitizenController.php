<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCitizenRequest;
use App\Http\Requests\UpdateCitizenRequest;
use App\Models\Citizen;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CitizenController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', Citizen::class);
        $citizens = $this->municipalScope->citizens(
            Citizen::query(),
            $this->authenticatedUser($request),
        )
            ->withCount(['households', 'housingApplications', 'contracts'])
            ->latest()
            ->paginate(15);

        return view('citizens.index', compact('citizens'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('createBackoffice', Citizen::class);

        return view('citizens.create');
    }

    public function store(StoreCitizenRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', Citizen::class);
        $citizen = new Citizen($request->validated());
        $citizen->forceFill([
            'municipality_id' => $this->authenticatedUser($request)->municipality_id,
        ])->save();
        $this->auditLogger->record(
            AuditEvents::CREATE,
            $citizen,
            'citizens',
            'create',
            'Munícipe criado no âmbito municipal.',
        );

        return to_route('citizens.index')
            ->with('success', 'Munícipe criado com sucesso.');
    }

    public function show(Request $request, Citizen $citizen): View
    {
        Gate::authorize('viewBackoffice', $citizen);
        $citizen->load([
            'households',
            'housingApplications.household',
            'contracts.housingUnit',
            'maintenanceRequests.housingUnit',
            'documents',
        ]);

        return view('citizens.show', compact('citizen'));
    }

    public function edit(Request $request, Citizen $citizen): View
    {
        Gate::authorize('updateBackoffice', $citizen);

        return view('citizens.edit', compact('citizen'));
    }

    public function update(UpdateCitizenRequest $request, Citizen $citizen): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $citizen);
        $citizen->update($request->validated());
        $this->auditLogger->record(
            AuditEvents::UPDATE,
            $citizen,
            'citizens',
            'update',
            'Munícipe atualizado no âmbito municipal.',
        );

        return to_route('citizens.index')
            ->with('success', 'Munícipe atualizado com sucesso.');
    }

    public function destroy(Request $request, Citizen $citizen): RedirectResponse
    {
        Gate::authorize('deleteBackoffice', $citizen);
        $citizen->delete();
        $this->auditLogger->record(
            AuditEvents::DELETE,
            $citizen,
            'citizens',
            'delete',
            'Munícipe removido no âmbito municipal.',
        );

        return to_route('citizens.index')
            ->with('success', 'Munícipe eliminado com sucesso.');
    }
}
