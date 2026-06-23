<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmTenantPaymentRequest;
use App\Http\Requests\RegisterTenantPaymentRequest;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Services\TenantBilling\TenantPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TenantPaymentController extends Controller
{
    public function __construct(private readonly TenantPaymentService $payments) {}

    public function index(): View
    {
        Gate::authorize('viewAny', TenantPayment::class);

        $payments = TenantPayment::query()
            ->with(['tenant', 'invoice', 'leaseContract.housingUnit'])
            ->latest('payment_date')
            ->paginate(20);

        return view('backoffice.tenant-payments.index', compact('payments'));
    }

    public function show(TenantPayment $tenantPayment): View
    {
        Gate::authorize('view', $tenantPayment);
        $tenantPayment->load(['tenant', 'invoice', 'tenantFinancialAccount', 'leaseContract.housingUnit']);

        return view('backoffice.tenant-payments.show', compact('tenantPayment'));
    }

    public function store(RegisterTenantPaymentRequest $request): RedirectResponse
    {
        Gate::authorize('create', TenantPayment::class);
        $data = $request->validated();
        $invoice = TenantInvoice::query()->whereKey((int) $data['tenant_invoice_id'])->firstOrFail();
        $payment = $this->payments->registerForInvoice($invoice, $this->authenticatedUser($request), $data);

        return to_route('backoffice.tenant-operations.payments.show', $payment)->with('success', 'Pagamento operacional registado.');
    }

    public function confirm(ConfirmTenantPaymentRequest $request, TenantPayment $tenantPayment): RedirectResponse
    {
        Gate::authorize('update', $tenantPayment);
        $this->payments->confirm($tenantPayment, $this->authenticatedUser($request));

        return to_route('backoffice.tenant-operations.payments.show', $tenantPayment)->with('success', 'Pagamento confirmado.');
    }
}
