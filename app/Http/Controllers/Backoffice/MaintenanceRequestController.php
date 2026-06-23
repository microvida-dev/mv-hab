<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\MaintenanceUrgency;
use App\Http\Controllers\Controller;
use App\Http\Requests\CloseMaintenanceRequestRequest;
use App\Http\Requests\RejectMaintenanceRequestRequest;
use App\Http\Requests\ResolveMaintenanceRequestRequest;
use App\Http\Requests\ReviewMaintenanceRequestRequest;
use App\Http\Requests\StoreMaintenanceRequestRequest;
use App\Http\Requests\UpdateMaintenanceRequestRequest;
use App\Models\HousingUnit;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceRequest;
use App\Services\Maintenance\MaintenanceRequestService;
use App\Services\Maintenance\MaintenanceStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MaintenanceRequestController extends Controller
{
    public function __construct(
        private readonly MaintenanceRequestService $requests,
        private readonly MaintenanceStatusService $statuses,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', MaintenanceRequest::class);
        $maintenanceRequests = MaintenanceRequest::query()
            ->with(['housingUnit', 'leaseContract.candidate', 'category', 'assignments.assignedUser', 'assignments.supplier'])
            ->latest()
            ->paginate(20);

        return view('backoffice.maintenance.requests.index', compact('maintenanceRequests'));
    }

    public function create(): View
    {
        Gate::authorize('create', MaintenanceRequest::class);
        $housingUnits = HousingUnit::query()->orderBy('code')->get(['id', 'code', 'address']);
        $categories = MaintenanceCategory::query()->where('is_active', true)->orderBy('name')->get();
        $urgencies = MaintenanceUrgency::options();

        return view('backoffice.maintenance.requests.create', compact('housingUnits', 'categories', 'urgencies'));
    }

    public function store(StoreMaintenanceRequestRequest $request): RedirectResponse
    {
        Gate::authorize('create', MaintenanceRequest::class);
        $maintenanceRequest = $this->requests->createFromBackoffice($this->authenticatedUser($request), $request->validated());

        return to_route('backoffice.maintenance.requests.show', $maintenanceRequest)->with('success', 'Pedido criado.');
    }

    public function show(MaintenanceRequest $maintenanceRequest): View
    {
        Gate::authorize('view', $maintenanceRequest);
        $maintenanceRequest->load(['housingUnit', 'leaseContract.candidate', 'category', 'statusHistories.changedBy', 'assignments.assignedUser', 'assignments.supplier', 'interventions', 'attachments', 'costs.supplier']);

        return view('backoffice.maintenance.requests.show', compact('maintenanceRequest'));
    }

    public function edit(MaintenanceRequest $maintenanceRequest): View
    {
        Gate::authorize('update', $maintenanceRequest);
        $categories = MaintenanceCategory::query()->where('is_active', true)->orderBy('name')->get();
        $urgencies = MaintenanceUrgency::options();

        return view('backoffice.maintenance.requests.edit', compact('maintenanceRequest', 'categories', 'urgencies'));
    }

    public function update(UpdateMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        Gate::authorize('update', $maintenanceRequest);
        $maintenanceRequest->update($request->validated());

        return to_route('backoffice.maintenance.requests.show', $maintenanceRequest)->with('success', 'Pedido atualizado.');
    }

    public function review(ReviewMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        Gate::authorize('manage', $maintenanceRequest);
        $this->statuses->review($maintenanceRequest, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Pedido em análise.');
    }

    public function schedule(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        Gate::authorize('manage', $maintenanceRequest);
        $data = $request->validate(['scheduled_for' => ['nullable', 'date']]);
        $this->statuses->schedule($maintenanceRequest, $this->authenticatedUser($request), $data['scheduled_for'] ?? null);

        return back()->with('success', 'Pedido agendado.');
    }

    public function start(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        Gate::authorize('manage', $maintenanceRequest);
        $this->statuses->start($maintenanceRequest, $this->authenticatedUser($request));

        return back()->with('success', 'Pedido em execução.');
    }

    public function resolve(ResolveMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        Gate::authorize('manage', $maintenanceRequest);
        $this->statuses->resolve($maintenanceRequest, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Pedido resolvido.');
    }

    public function reject(RejectMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        Gate::authorize('reject', $maintenanceRequest);
        $this->statuses->reject($maintenanceRequest, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Pedido rejeitado.');
    }

    public function close(CloseMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        Gate::authorize('manage', $maintenanceRequest);
        $this->statuses->close($maintenanceRequest, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Pedido fechado.');
    }

    public function cancel(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        Gate::authorize('manage', $maintenanceRequest);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:5000']]);
        $this->statuses->cancel($maintenanceRequest, $this->authenticatedUser($request), $data['reason'] ?? null);

        return back()->with('success', 'Pedido cancelado.');
    }
}
