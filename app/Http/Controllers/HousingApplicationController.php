<?php

namespace App\Http\Controllers;

use App\Enums\HousingApplicationStatus;
use App\Http\Requests\StoreHousingApplicationRequest;
use App\Http\Requests\UpdateHousingApplicationRequest;
use App\Models\Citizen;
use App\Models\Household;
use App\Models\HousingApplication;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HousingApplicationController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', HousingApplication::class);
        $applications = $this->municipalScope->housingApplications(
            HousingApplication::query(),
            $this->authenticatedUser($request),
        )
            ->with(['citizen', 'household'])
            ->latest()
            ->paginate(15);

        return view('applications.index', compact('applications'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('createBackoffice', HousingApplication::class);
        $actor = $this->authenticatedUser($request);
        $citizens = $this->municipalScope->citizens(Citizen::query(), $actor)
            ->orderBy('name')
            ->get(['id', 'name']);
        $households = $this->municipalScope->households(Household::query(), $actor)
            ->with('citizen:id,name')
            ->orderBy('name')
            ->get(['id', 'citizen_id', 'name']);
        $statuses = HousingApplicationStatus::options();

        return view('applications.create', compact('citizens', 'households', 'statuses'));
    }

    public function store(StoreHousingApplicationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (($validated['status'] ?? null) === HousingApplicationStatus::Submitted->value && empty($validated['submitted_at'])) {
            $validated['submitted_at'] = now();
        }

        Gate::authorize('createBackoffice', HousingApplication::class);
        $application = new HousingApplication($validated);
        $application->forceFill([
            'municipality_id' => $this->authenticatedUser($request)->municipality_id,
        ])->save();
        $this->auditLogger->record(
            AuditEvents::CREATE,
            $application,
            'applications',
            'create',
            'Candidatura legada criada no âmbito municipal.',
        );

        return to_route('applications.index')
            ->with('success', 'Candidatura criada com sucesso.');
    }

    public function show(Request $request, HousingApplication $application): View
    {
        Gate::authorize('viewBackoffice', $application);
        $application->load(['citizen', 'household', 'documents']);

        return view('applications.show', compact('application'));
    }

    public function edit(Request $request, HousingApplication $application): View
    {
        Gate::authorize('updateBackoffice', $application);
        $actor = $this->authenticatedUser($request);
        $citizens = $this->municipalScope->citizens(Citizen::query(), $actor)
            ->orderBy('name')
            ->get(['id', 'name']);
        $households = $this->municipalScope->households(Household::query(), $actor)
            ->with('citizen:id,name')
            ->orderBy('name')
            ->get(['id', 'citizen_id', 'name']);
        $statuses = HousingApplicationStatus::options();

        return view('applications.edit', compact('application', 'citizens', 'households', 'statuses'));
    }

    public function update(UpdateHousingApplicationRequest $request, HousingApplication $application): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $application);
        $validated = $request->validated();

        if (($validated['status'] ?? null) === HousingApplicationStatus::Submitted->value && empty($validated['submitted_at'])) {
            $validated['submitted_at'] = $application->submitted_at ?? now();
        }

        $application->update($validated);
        $this->auditLogger->record(
            AuditEvents::UPDATE,
            $application,
            'applications',
            'update',
            'Candidatura legada atualizada no âmbito municipal.',
        );

        return to_route('applications.index')
            ->with('success', 'Candidatura atualizada com sucesso.');
    }

    public function destroy(Request $request, HousingApplication $application): RedirectResponse
    {
        Gate::authorize('deleteBackoffice', $application);
        $application->delete();
        $this->auditLogger->record(
            AuditEvents::DELETE,
            $application,
            'applications',
            'delete',
            'Candidatura legada removida no âmbito municipal.',
        );

        return to_route('applications.index')
            ->with('success', 'Candidatura eliminada com sucesso.');
    }
}
