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
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MaintenanceRequestController extends Controller
{
    public function __construct(
        private readonly MaintenanceRequestService $requests,
        private readonly MaintenanceStatusService $statuses,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize(
            'viewAnyBackoffice',
            MaintenanceRequest::class,
        );

        $actor = $this->authenticatedUser($request);

        $maintenanceRequests = $this->municipalScope
            ->maintenanceRequests(
                MaintenanceRequest::query(),
                $actor,
            )
            ->with([
                'housingUnit',
                'leaseContract.candidate',
                'category',
                'assignments.assignedUser',
                'assignments.supplier',
            ])
            ->latest()
            ->paginate(20);

        return view(
            'backoffice.maintenance.requests.index',
            compact('maintenanceRequests'),
        );
    }

    public function create(Request $request): View
    {
        Gate::authorize(
            'createBackoffice',
            MaintenanceRequest::class,
        );

        $actor = $this->authenticatedUser($request);

        $housingUnits = $this->municipalScope
            ->housingUnits(HousingUnit::query(), $actor)
            ->orderBy('code')
            ->get(['id', 'code', 'address']);

        $categories = $this->municipalScope
            ->maintenanceCategories(
                MaintenanceCategory::query(),
                $actor,
            )
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $urgencies = MaintenanceUrgency::options();

        return view(
            'backoffice.maintenance.requests.create',
            compact('housingUnits', 'categories', 'urgencies'),
        );
    }

    public function store(
        StoreMaintenanceRequestRequest $request,
    ): RedirectResponse {
        Gate::authorize(
            'createBackoffice',
            MaintenanceRequest::class,
        );

        $maintenanceRequest = $this->requests
            ->createFromBackoffice(
                $this->authenticatedUser($request),
                $request->validated(),
            );

        return to_route(
            'backoffice.maintenance.requests.show',
            $maintenanceRequest,
        )->with('success', 'Pedido criado.');
    }

    public function show(
        MaintenanceRequest $maintenanceRequest,
    ): View {
        Gate::authorize(
            'viewBackoffice',
            $maintenanceRequest,
        );

        $maintenanceRequest->load([
            'housingUnit',
            'leaseContract.candidate',
            'category',
            'statusHistories.changedBy',
            'assignments.assignedUser',
            'assignments.supplier',
            'interventions',
            'attachments',
            'costs.supplier',
        ]);

        return view(
            'backoffice.maintenance.requests.show',
            compact('maintenanceRequest'),
        );
    }

    public function edit(
        Request $request,
        MaintenanceRequest $maintenanceRequest,
    ): View {
        Gate::authorize(
            'updateBackoffice',
            $maintenanceRequest,
        );

        $categories = $this->municipalScope
            ->maintenanceCategories(
                MaintenanceCategory::query(),
                $this->authenticatedUser($request),
            )
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $urgencies = MaintenanceUrgency::options();

        return view(
            'backoffice.maintenance.requests.edit',
            compact(
                'maintenanceRequest',
                'categories',
                'urgencies',
            ),
        );
    }

    public function update(
        UpdateMaintenanceRequestRequest $request,
        MaintenanceRequest $maintenanceRequest,
    ): RedirectResponse {
        Gate::authorize(
            'updateBackoffice',
            $maintenanceRequest,
        );

        $maintenanceRequest->update(
            $request->validated(),
        );

        return to_route(
            'backoffice.maintenance.requests.show',
            $maintenanceRequest,
        )->with('success', 'Pedido atualizado.');
    }

    public function review(
        ReviewMaintenanceRequestRequest $request,
        MaintenanceRequest $maintenanceRequest,
    ): RedirectResponse {
        Gate::authorize(
            'reviewBackoffice',
            $maintenanceRequest,
        );

        $this->statuses->review(
            $maintenanceRequest,
            $this->authenticatedUser($request),
            $request->validated(),
        );

        return back()->with('success', 'Pedido em análise.');
    }

    public function schedule(
        Request $request,
        MaintenanceRequest $maintenanceRequest,
    ): RedirectResponse {
        Gate::authorize(
            'scheduleBackoffice',
            $maintenanceRequest,
        );

        $data = $request->validate([
            'scheduled_for' => ['nullable', 'date'],
        ]);

        $this->statuses->schedule(
            $maintenanceRequest,
            $this->authenticatedUser($request),
            $data['scheduled_for'] ?? null,
        );

        return back()->with('success', 'Pedido agendado.');
    }

    public function start(
        Request $request,
        MaintenanceRequest $maintenanceRequest,
    ): RedirectResponse {
        Gate::authorize(
            'startBackoffice',
            $maintenanceRequest,
        );

        $this->statuses->start(
            $maintenanceRequest,
            $this->authenticatedUser($request),
        );

        return back()->with('success', 'Pedido em execução.');
    }

    public function resolve(
        ResolveMaintenanceRequestRequest $request,
        MaintenanceRequest $maintenanceRequest,
    ): RedirectResponse {
        Gate::authorize(
            'resolveBackoffice',
            $maintenanceRequest,
        );

        $this->statuses->resolve(
            $maintenanceRequest,
            $this->authenticatedUser($request),
            $request->validated(),
        );

        return back()->with('success', 'Pedido resolvido.');
    }

    public function reject(
        RejectMaintenanceRequestRequest $request,
        MaintenanceRequest $maintenanceRequest,
    ): RedirectResponse {
        Gate::authorize(
            'rejectBackoffice',
            $maintenanceRequest,
        );

        $this->statuses->reject(
            $maintenanceRequest,
            $this->authenticatedUser($request),
            $request->validated(),
        );

        return back()->with('success', 'Pedido rejeitado.');
    }

    public function close(
        CloseMaintenanceRequestRequest $request,
        MaintenanceRequest $maintenanceRequest,
    ): RedirectResponse {
        Gate::authorize(
            'closeBackoffice',
            $maintenanceRequest,
        );

        $this->statuses->close(
            $maintenanceRequest,
            $this->authenticatedUser($request),
            $request->validated(),
        );

        return back()->with('success', 'Pedido fechado.');
    }

    public function cancel(
        Request $request,
        MaintenanceRequest $maintenanceRequest,
    ): RedirectResponse {
        Gate::authorize(
            'cancelBackoffice',
            $maintenanceRequest,
        );

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->statuses->cancel(
            $maintenanceRequest,
            $this->authenticatedUser($request),
            $data['reason'] ?? null,
        );

        return back()->with('success', 'Pedido cancelado.');
    }
}
