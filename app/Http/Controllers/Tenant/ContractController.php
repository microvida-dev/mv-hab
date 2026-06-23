<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\TenantPortal\TenantPortalAccessService;
use Illuminate\Contracts\View\View;

class ContractController extends Controller
{
    public function __construct(private readonly TenantPortalAccessService $access) {}

    public function index(): View
    {
        $tenant = $this->currentUser();
        abort_unless($this->access->hasActiveAccess($tenant), 403);

        $contracts = $this->access->activeContracts($tenant)
            ->with(['housingUnit', 'deposit', 'financialAccount'])
            ->latest()
            ->paginate(15);

        return view('tenant.contracts.index', compact('contracts'));
    }

    public function show(Contract $contract): View
    {
        abort_unless($this->access->canAccessContract($this->currentUser(), $contract), 403);

        $contract->load(['housingUnit', 'deposit', 'generatedDocuments', 'financialAccount']);

        return view('tenant.contracts.show', compact('contract'));
    }
}
