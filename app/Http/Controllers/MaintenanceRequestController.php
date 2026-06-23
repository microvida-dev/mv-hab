<?php

namespace App\Http\Controllers;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceRequestStatus;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Http\Requests\UpdateMaintenanceRequest;
use App\Models\Citizen;
use App\Models\HousingUnit;
use App\Models\MaintenanceRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class MaintenanceRequestController extends Controller
{
    public function index(): View
    {
        $maintenanceRequests = MaintenanceRequest::query()
            ->with(['housingUnit', 'citizen'])
            ->latest()
            ->paginate(15);

        return view('maintenance-requests.index', compact('maintenanceRequests'));
    }

    public function create(): View
    {
        $housingUnits = HousingUnit::query()
            ->orderBy('code')
            ->get(['id', 'code', 'address']);
        $citizens = Citizen::query()
            ->orderBy('name')
            ->get(['id', 'name']);
        $priorities = MaintenancePriority::options();
        $statuses = MaintenanceRequestStatus::options();

        return view('maintenance-requests.create', compact('housingUnits', 'citizens', 'priorities', 'statuses'));
    }

    public function store(StoreMaintenanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (($validated['status'] ?? null) === MaintenanceRequestStatus::Resolved->value && empty($validated['resolved_at'])) {
            $validated['resolved_at'] = now();
        }

        if (($validated['status'] ?? null) !== MaintenanceRequestStatus::Resolved->value) {
            $validated['resolved_at'] = null;
        }

        MaintenanceRequest::create($validated);

        return to_route('maintenance-requests.index')
            ->with('success', 'Pedido de manutenção criado com sucesso.');
    }

    public function show(MaintenanceRequest $maintenanceRequest): View
    {
        $maintenanceRequest->load(['housingUnit', 'citizen']);

        return view('maintenance-requests.show', compact('maintenanceRequest'));
    }

    public function edit(MaintenanceRequest $maintenanceRequest): View
    {
        $housingUnits = HousingUnit::query()
            ->orderBy('code')
            ->get(['id', 'code', 'address']);
        $citizens = Citizen::query()
            ->orderBy('name')
            ->get(['id', 'name']);
        $priorities = MaintenancePriority::options();
        $statuses = MaintenanceRequestStatus::options();

        return view('maintenance-requests.edit', compact('maintenanceRequest', 'housingUnits', 'citizens', 'priorities', 'statuses'));
    }

    public function update(UpdateMaintenanceRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $validated = $request->validated();

        if (($validated['status'] ?? null) === MaintenanceRequestStatus::Resolved->value && empty($validated['resolved_at'])) {
            $validated['resolved_at'] = $maintenanceRequest->resolved_at ?? now();
        }

        if (($validated['status'] ?? null) !== MaintenanceRequestStatus::Resolved->value) {
            $validated['resolved_at'] = null;
        }

        $maintenanceRequest->update($validated);

        return to_route('maintenance-requests.index')
            ->with('success', 'Pedido de manutenção atualizado com sucesso.');
    }

    public function destroy(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $maintenanceRequest->delete();

        return to_route('maintenance-requests.index')
            ->with('success', 'Pedido de manutenção eliminado com sucesso.');
    }
}
