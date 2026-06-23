<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\MaintenanceUrgency;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceRequestRequest;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceRequest;
use App\Services\Maintenance\MaintenanceRequestService;
use App\Services\TenantPortal\TenantPortalAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MaintenanceRequestController extends Controller
{
    public function __construct(
        private readonly TenantPortalAccessService $access,
        private readonly MaintenanceRequestService $requests,
    ) {}

    public function index(): View
    {
        $tenant = $this->currentUser();
        abort_unless($this->access->hasActiveAccess($tenant), 403);
        Gate::authorize('viewAny', MaintenanceRequest::class);

        $maintenanceRequests = $this->requests->tenantScope($tenant)
            ->with(['housingUnit', 'category'])
            ->latest()
            ->paginate(15);

        return view('tenant.maintenance.index', compact('maintenanceRequests'));
    }

    public function create(): View
    {
        abort_unless($this->access->hasActiveAccess($this->currentUser()), 403);
        Gate::authorize('create', MaintenanceRequest::class);

        return view('tenant.maintenance.create', [
            'categories' => MaintenanceCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'urgencies' => MaintenanceUrgency::options(),
        ]);
    }

    public function store(StoreMaintenanceRequestRequest $request): RedirectResponse
    {
        $tenant = $this->authenticatedUser($request);
        abort_unless($this->access->hasActiveAccess($tenant), 403);
        Gate::authorize('create', MaintenanceRequest::class);

        $maintenanceRequest = $this->requests->createFromTenant($tenant, $request->validated());

        return to_route('tenant.maintenance.show', $maintenanceRequest)->with('success', 'Pedido de manutenção submetido.');
    }

    public function show(MaintenanceRequest $maintenanceRequest): View
    {
        Gate::authorize('view', $maintenanceRequest);
        $maintenanceRequest->load(['housingUnit', 'category', 'statusHistories', 'attachments' => fn ($query) => $query->where('visible_to_tenant', true), 'interventions']);

        return view('tenant.maintenance.show', compact('maintenanceRequest'));
    }
}
