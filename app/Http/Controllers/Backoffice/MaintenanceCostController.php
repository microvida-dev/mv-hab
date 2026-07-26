<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveMaintenanceCostRequest;
use App\Http\Requests\RejectMaintenanceCostRequest;
use App\Http\Requests\StoreMaintenanceCostRequest;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceRequest;
use App\Services\Maintenance\MaintenanceCostService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MaintenanceCostController extends Controller
{
    public function __construct(
        private readonly MaintenanceCostService $costs,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize(
            'viewAnyBackoffice',
            MaintenanceCost::class,
        );

        $costs = $this->municipalScope
            ->maintenanceCosts(
                MaintenanceCost::query(),
                $this->authenticatedUser($request),
            )
            ->with([
                'maintenanceRequest',
                'housingUnit',
                'supplier',
            ])
            ->latest()
            ->paginate(20);

        return view(
            'backoffice.maintenance.costs.index',
            compact('costs'),
        );
    }

    public function store(
        StoreMaintenanceCostRequest $request,
        MaintenanceRequest $maintenanceRequest,
    ): RedirectResponse {
        Gate::authorize(
            'createBackoffice',
            [
                MaintenanceCost::class,
                $maintenanceRequest,
            ],
        );

        $this->costs->store(
            $maintenanceRequest,
            $this->authenticatedUser($request),
            $request->validated(),
        );

        return back()->with(
            'success',
            'Custo registado.',
        );
    }

    public function approve(
        ApproveMaintenanceCostRequest $request,
        MaintenanceCost $maintenanceCost,
    ): RedirectResponse {
        Gate::authorize(
            'approveBackoffice',
            $maintenanceCost,
        );

        $this->costs->approve(
            $maintenanceCost,
            $this->authenticatedUser($request),
        );

        return back()->with(
            'success',
            'Custo aprovado.',
        );
    }

    public function reject(
        RejectMaintenanceCostRequest $request,
        MaintenanceCost $maintenanceCost,
    ): RedirectResponse {
        Gate::authorize(
            'rejectBackoffice',
            $maintenanceCost,
        );

        $this->costs->reject(
            $maintenanceCost,
            $this->authenticatedUser($request),
            $request->validated('rejection_reason'),
        );

        return back()->with(
            'success',
            'Custo rejeitado.',
        );
    }
}
