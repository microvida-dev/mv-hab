<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteMaintenanceInterventionRequest;
use App\Http\Requests\StoreMaintenanceInterventionRequest;
use App\Models\MaintenanceIntervention;
use App\Models\MaintenanceRequest;
use App\Services\Maintenance\MaintenanceInterventionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MaintenanceInterventionController extends Controller
{
    public function __construct(private readonly MaintenanceInterventionService $interventions) {}

    public function store(StoreMaintenanceInterventionRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        Gate::authorize('create', MaintenanceIntervention::class);
        $this->interventions->store($maintenanceRequest, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Intervenção registada.');
    }

    public function show(MaintenanceIntervention $maintenanceIntervention): View
    {
        Gate::authorize('view', $maintenanceIntervention);
        $maintenanceIntervention->load(['maintenanceRequest', 'housingUnit', 'leaseContract', 'performedBy', 'supplier', 'attachments', 'costs']);

        return view('backoffice.maintenance.interventions.show', compact('maintenanceIntervention'));
    }

    public function start(Request $request, MaintenanceIntervention $maintenanceIntervention): RedirectResponse
    {
        Gate::authorize('update', $maintenanceIntervention);
        $this->interventions->start($maintenanceIntervention, $this->authenticatedUser($request));

        return back()->with('success', 'Intervenção iniciada.');
    }

    public function complete(CompleteMaintenanceInterventionRequest $request, MaintenanceIntervention $maintenanceIntervention): RedirectResponse
    {
        Gate::authorize('update', $maintenanceIntervention);
        $this->interventions->complete($maintenanceIntervention, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Intervenção concluída.');
    }

    public function cancel(Request $request, MaintenanceIntervention $maintenanceIntervention): RedirectResponse
    {
        Gate::authorize('update', $maintenanceIntervention);
        $this->interventions->cancel($maintenanceIntervention, $this->authenticatedUser($request));

        return back()->with('success', 'Intervenção cancelada.');
    }
}
