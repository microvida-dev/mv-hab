<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PropertyInspection;
use App\Services\TenantPortal\TenantPortalAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class InspectionController extends Controller
{
    public function __construct(private readonly TenantPortalAccessService $access) {}

    public function index(): View
    {
        $tenant = $this->currentUser();
        abort_unless($this->access->hasActiveAccess($tenant), 403);
        Gate::authorize('viewAny', PropertyInspection::class);

        $inspections = PropertyInspection::query()
            ->where('tenant_visible', true)
            ->whereHas('leaseContract', fn ($query) => $query->where('user_id', $tenant->id))
            ->with(['housingUnit', 'report'])
            ->latest()
            ->paginate(15);

        return view('tenant.inspections.index', compact('inspections'));
    }

    public function show(PropertyInspection $propertyInspection): View
    {
        Gate::authorize('view', $propertyInspection);
        $propertyInspection->load(['housingUnit', 'items', 'attachments' => fn ($query) => $query->where('visible_to_tenant', true), 'report']);

        return view('tenant.inspections.show', compact('propertyInspection'));
    }
}
