<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceAssignmentRequest;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceRequest;
use App\Services\Maintenance\MaintenanceAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MaintenanceAssignmentController extends Controller
{
    public function __construct(private readonly MaintenanceAssignmentService $assignments) {}

    public function store(StoreMaintenanceAssignmentRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        Gate::authorize('create', MaintenanceAssignment::class);
        $this->assignments->assign($maintenanceRequest, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Atribuição registada.');
    }

    public function cancel(Request $request, MaintenanceAssignment $maintenanceAssignment): RedirectResponse
    {
        Gate::authorize('update', $maintenanceAssignment);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:3000']]);
        $this->assignments->cancel($maintenanceAssignment, $this->authenticatedUser($request), $data['reason'] ?? null);

        return back()->with('success', 'Atribuição cancelada.');
    }
}
