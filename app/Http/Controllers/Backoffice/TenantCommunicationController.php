<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantCommunicationMessageRequest;
use App\Http\Requests\StoreTenantCommunicationRequest;
use App\Models\TenantCommunication;
use App\Models\User;
use App\Services\TenantCommunications\TenantCommunicationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TenantCommunicationController extends Controller
{
    public function __construct(private readonly TenantCommunicationService $communications) {}

    public function index(): View
    {
        Gate::authorize('viewAny', TenantCommunication::class);

        $communications = TenantCommunication::query()
            ->with(['tenant', 'leaseContract.housingUnit'])
            ->latest('last_message_at')
            ->paginate(20);

        return view('backoffice.tenant-communications.index', compact('communications'));
    }

    public function show(TenantCommunication $tenantCommunication): View
    {
        Gate::authorize('view', $tenantCommunication);
        $tenantCommunication->load(['tenant', 'leaseContract.housingUnit', 'messages.sender']);

        return view('backoffice.tenant-communications.show', compact('tenantCommunication'));
    }

    public function store(StoreTenantCommunicationRequest $request): RedirectResponse
    {
        Gate::authorize('create', TenantCommunication::class);
        $data = $request->validated();
        $tenant = User::query()->whereKey((int) $data['user_id'])->firstOrFail();
        $communication = $this->communications->open($tenant, $this->authenticatedUser($request), $data);

        return to_route('backoffice.tenant-operations.communications.show', $communication)->with('success', 'Comunicação aberta.');
    }

    public function message(StoreTenantCommunicationMessageRequest $request, TenantCommunication $tenantCommunication): RedirectResponse
    {
        Gate::authorize('update', $tenantCommunication);
        $this->communications->message($tenantCommunication, $this->authenticatedUser($request), $request->validated());

        return to_route('backoffice.tenant-operations.communications.show', $tenantCommunication)->with('success', 'Mensagem registada.');
    }
}
