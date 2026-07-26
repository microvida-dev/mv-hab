<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceSupplierRequest;
use App\Http\Requests\UpdateMaintenanceSupplierRequest;
use App\Models\MaintenanceSupplier;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MaintenanceSupplierController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', MaintenanceSupplier::class);

        $suppliers = $this->municipalScope
            ->maintenanceSuppliers(
                MaintenanceSupplier::query(),
                $this->authenticatedUser($request),
            )
            ->latest()
            ->paginate(20);

        return view(
            'backoffice.maintenance.suppliers.index',
            compact('suppliers'),
        );
    }

    public function create(): View
    {
        Gate::authorize('create', MaintenanceSupplier::class);

        return view('backoffice.maintenance.suppliers.create');
    }

    public function store(
        StoreMaintenanceSupplierRequest $request,
    ): RedirectResponse {
        Gate::authorize('create', MaintenanceSupplier::class);

        $actor = $this->authenticatedUser($request);
        $municipalityId = $actor->municipality_id;

        abort_if($municipalityId === null, 403);

        $supplier = new MaintenanceSupplier(
            $request->validated(),
        );

        $supplier->forceFill([
            'municipality_id' => $municipalityId,
        ])->save();

        return to_route(
            'backoffice.maintenance.suppliers.show',
            $supplier,
        )->with('success', 'Fornecedor criado.');
    }

    public function show(
        MaintenanceSupplier $maintenanceSupplier,
    ): View {
        Gate::authorize('view', $maintenanceSupplier);

        $maintenanceSupplier->load([
            'assignments.maintenanceRequest',
            'costs',
        ]);

        return view(
            'backoffice.maintenance.suppliers.show',
            compact('maintenanceSupplier'),
        );
    }

    public function edit(
        MaintenanceSupplier $maintenanceSupplier,
    ): View {
        Gate::authorize('update', $maintenanceSupplier);

        return view(
            'backoffice.maintenance.suppliers.edit',
            compact('maintenanceSupplier'),
        );
    }

    public function update(
        UpdateMaintenanceSupplierRequest $request,
        MaintenanceSupplier $maintenanceSupplier,
    ): RedirectResponse {
        Gate::authorize('update', $maintenanceSupplier);

        $maintenanceSupplier->update(
            $request->validated(),
        );

        return to_route(
            'backoffice.maintenance.suppliers.show',
            $maintenanceSupplier,
        )->with('success', 'Fornecedor atualizado.');
    }
}
