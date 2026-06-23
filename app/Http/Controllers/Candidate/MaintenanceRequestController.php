<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\MaintenanceUrgency;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceRequestRequest;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceRequest;
use App\Services\Maintenance\MaintenanceRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MaintenanceRequestController extends Controller
{
    public function __construct(private readonly MaintenanceRequestService $requests) {}

    public function overview(): View
    {
        Gate::authorize('viewAny', MaintenanceRequest::class);
        $counts = $this->requests->tenantScope($this->currentUser())
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('candidate.maintenance.index', compact('counts'));
    }

    public function index(): View
    {
        Gate::authorize('viewAny', MaintenanceRequest::class);
        $maintenanceRequests = $this->requests->tenantScope($this->currentUser())
            ->with(['housingUnit', 'category'])
            ->latest()
            ->paginate(15);

        return view('candidate.maintenance.requests.index', compact('maintenanceRequests'));
    }

    public function create(): View
    {
        Gate::authorize('create', MaintenanceRequest::class);
        $categories = MaintenanceCategory::query()->where('is_active', true)->orderBy('name')->get();
        $urgencies = MaintenanceUrgency::options();

        return view('candidate.maintenance.requests.create', compact('categories', 'urgencies'));
    }

    public function store(StoreMaintenanceRequestRequest $request): RedirectResponse
    {
        Gate::authorize('create', MaintenanceRequest::class);
        $maintenanceRequest = $this->requests->createFromTenant($this->authenticatedUser($request), $request->validated());

        return to_route('candidate.maintenance.requests.show', $maintenanceRequest)->with('success', 'Pedido submetido.');
    }

    public function show(MaintenanceRequest $maintenanceRequest): View
    {
        Gate::authorize('view', $maintenanceRequest);
        $maintenanceRequest->load(['housingUnit', 'category', 'statusHistories', 'attachments' => fn ($query) => $query->where('visible_to_tenant', true), 'interventions']);

        return view('candidate.maintenance.requests.show', compact('maintenanceRequest'));
    }
}
