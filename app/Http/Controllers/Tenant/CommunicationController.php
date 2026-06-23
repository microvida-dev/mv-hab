<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantCommunicationRequest;
use App\Models\TenantCommunication;
use App\Services\TenantCommunications\TenantCommunicationService;
use App\Services\TenantPortal\TenantPortalAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CommunicationController extends Controller
{
    public function __construct(
        private readonly TenantPortalAccessService $access,
        private readonly TenantCommunicationService $communications,
    ) {}

    public function index(): View
    {
        $tenant = $this->currentUser();
        abort_unless($this->access->hasActiveAccess($tenant), 403);
        Gate::authorize('viewAny', TenantCommunication::class);

        $communications = TenantCommunication::query()
            ->where('user_id', $tenant->id)
            ->with('messages')
            ->latest('last_message_at')
            ->paginate(15);

        return view('tenant.communications.index', compact('communications'));
    }

    public function create(): View
    {
        $tenant = $this->currentUser();
        abort_unless($this->access->hasActiveAccess($tenant), 403);
        Gate::authorize('create', TenantCommunication::class);

        return view('tenant.communications.create', [
            'contracts' => $this->access->activeContracts($tenant)->with('housingUnit')->get(),
        ]);
    }

    public function store(StoreTenantCommunicationRequest $request): RedirectResponse
    {
        $tenant = $this->authenticatedUser($request);
        abort_unless($this->access->hasActiveAccess($tenant), 403);
        Gate::authorize('create', TenantCommunication::class);

        $communication = $this->communications->open($tenant, $tenant, $request->validated());

        return to_route('tenant.communications.show', $communication)->with('success', 'Comunicação enviada aos serviços municipais.');
    }

    public function show(TenantCommunication $tenantCommunication): View
    {
        Gate::authorize('view', $tenantCommunication);
        $tenantCommunication->load(['leaseContract.housingUnit', 'messages.sender']);

        return view('tenant.communications.show', compact('tenantCommunication'));
    }
}
