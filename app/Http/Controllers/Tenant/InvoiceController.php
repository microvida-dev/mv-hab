<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantInvoice;
use App\Services\TenantBilling\TenantInvoiceService;
use App\Services\TenantPortal\TenantPortalAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly TenantPortalAccessService $access,
        private readonly TenantInvoiceService $invoices,
    ) {}

    public function index(): View
    {
        $tenant = $this->currentUser();
        abort_unless($this->access->hasActiveAccess($tenant), 403);
        Gate::authorize('viewAny', TenantInvoice::class);

        $invoices = $this->invoices->tenantScope($tenant)
            ->with(['leaseContract.housingUnit', 'payments'])
            ->latest('issue_date')
            ->paginate(15);

        return view('tenant.invoices.index', compact('invoices'));
    }

    public function show(TenantInvoice $tenantInvoice): View
    {
        Gate::authorize('view', $tenantInvoice);
        $tenantInvoice->load(['leaseContract.housingUnit', 'tenantFinancialAccount', 'payments']);

        return view('tenant.invoices.show', compact('tenantInvoice'));
    }
}
