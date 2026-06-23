<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantCommunicationMessageRequest;
use App\Models\TenantCommunication;
use App\Services\TenantCommunications\TenantCommunicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CommunicationMessageController extends Controller
{
    public function __construct(private readonly TenantCommunicationService $communications) {}

    public function store(StoreTenantCommunicationMessageRequest $request, TenantCommunication $tenantCommunication): RedirectResponse
    {
        Gate::authorize('update', $tenantCommunication);
        $this->communications->message($tenantCommunication, $this->authenticatedUser($request), $request->validated());

        return to_route('tenant.communications.show', $tenantCommunication)->with('success', 'Mensagem enviada.');
    }
}
