<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\TenantPortal\TenantDashboardService;
use App\Services\TenantPortal\TenantPortalAccessService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TenantPortalAccessService $access,
        private readonly TenantDashboardService $dashboard,
    ) {}

    public function __invoke(): View
    {
        $tenant = $this->currentUser();
        abort_unless($this->access->hasActiveAccess($tenant), 403);

        $this->access->ensureForUser($tenant, $tenant);

        return view('tenant.dashboard', [
            'summary' => $this->dashboard->summary($tenant),
            'contracts' => $this->access->activeContracts($tenant)->with(['housingUnit', 'financialAccount'])->latest()->get(),
        ]);
    }
}
