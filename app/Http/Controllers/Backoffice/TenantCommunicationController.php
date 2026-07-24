<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantCommunicationMessageRequest;
use App\Http\Requests\StoreTenantCommunicationRequest;
use App\Models\Contract;
use App\Models\TenantCommunication;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\TenantCommunications\TenantCommunicationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TenantCommunicationController extends Controller
{
    public function __construct(
        private readonly TenantCommunicationService $communications,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', TenantCommunication::class);

        $communications = $this->municipalScope
            ->tenantCommunications(
                TenantCommunication::query(),
                $this->authenticatedUser($request),
            )
            ->with(['tenant', 'leaseContract.housingUnit'])
            ->latest('last_message_at')
            ->paginate(20);

        return view('backoffice.tenant-communications.index', compact('communications'));
    }

    public function show(TenantCommunication $tenantCommunication): View
    {
        Gate::authorize('viewBackoffice', $tenantCommunication);
        $tenantCommunication->load(['tenant', 'leaseContract.housingUnit', 'messages.sender']);

        return view('backoffice.tenant-communications.show', compact('tenantCommunication'));
    }

    public function store(StoreTenantCommunicationRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', TenantCommunication::class);
        $data = $request->validated();
        $actor = $this->authenticatedUser($request);
        $tenant = $this->municipalScope
            ->users(User::query(), $actor)
            ->whereKey((int) $data['user_id'])
            ->firstOrFail();

        if (isset($data['lease_contract_id'])) {
            $contract = $this->municipalScope
                ->contracts(Contract::query(), $actor)
                ->where('user_id', $tenant->id)
                ->findOrFail((int) $data['lease_contract_id']);
            $data['lease_contract_id'] = $contract->id;
        }

        $communication = $this->communications->open($tenant, $actor, $data);

        return to_route('backoffice.tenant-operations.communications.show', $communication)->with('success', 'Comunicação aberta.');
    }

    public function message(StoreTenantCommunicationMessageRequest $request, TenantCommunication $tenantCommunication): RedirectResponse
    {
        Gate::authorize('messageBackoffice', $tenantCommunication);
        $this->communications->message($tenantCommunication, $this->authenticatedUser($request), $request->validated());

        return to_route('backoffice.tenant-operations.communications.show', $tenantCommunication)->with('success', 'Mensagem registada.');
    }
}
