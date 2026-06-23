<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantPayment;
use App\Services\TenantPortal\TenantPortalAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function __construct(private readonly TenantPortalAccessService $access) {}

    public function index(): View
    {
        $tenant = $this->currentUser();
        abort_unless($this->access->hasActiveAccess($tenant), 403);
        Gate::authorize('viewAny', TenantPayment::class);

        $payments = TenantPayment::query()
            ->where('user_id', $tenant->id)
            ->with(['invoice', 'leaseContract.housingUnit'])
            ->latest('payment_date')
            ->paginate(15);

        return view('tenant.payments.index', compact('payments'));
    }

    public function show(TenantPayment $tenantPayment): View
    {
        Gate::authorize('view', $tenantPayment);
        $tenantPayment->load(['invoice', 'leaseContract.housingUnit', 'tenantFinancialAccount']);

        return view('tenant.payments.show', compact('tenantPayment'));
    }
}
