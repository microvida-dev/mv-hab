<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateTenantInvoiceRequest;
use App\Models\Contract;
use App\Models\TenantInvoice;
use App\Services\TenantBilling\TenantInvoiceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TenantInvoiceController extends Controller
{
    public function __construct(private readonly TenantInvoiceService $invoices) {}

    public function index(): View
    {
        Gate::authorize('viewAny', TenantInvoice::class);

        $invoices = TenantInvoice::query()
            ->with(['tenant', 'leaseContract.housingUnit', 'payments'])
            ->latest('issue_date')
            ->paginate(20);

        return view('backoffice.tenant-invoices.index', compact('invoices'));
    }

    public function show(TenantInvoice $tenantInvoice): View
    {
        Gate::authorize('view', $tenantInvoice);
        $tenantInvoice->load(['tenant', 'tenantFinancialAccount', 'leaseContract.housingUnit', 'payments']);

        return view('backoffice.tenant-invoices.show', compact('tenantInvoice'));
    }

    public function store(GenerateTenantInvoiceRequest $request): RedirectResponse
    {
        Gate::authorize('create', TenantInvoice::class);
        $data = $request->validated();
        $contract = Contract::query()->whereKey((int) $data['lease_contract_id'])->firstOrFail();
        $invoice = $this->invoices->issueForContract($contract, $this->authenticatedUser($request), $data);

        return to_route('backoffice.tenant-operations.invoices.show', $invoice)->with('success', 'Fatura operacional emitida.');
    }
}
