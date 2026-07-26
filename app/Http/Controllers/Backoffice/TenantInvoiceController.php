<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateTenantInvoiceRequest;
use App\Models\Contract;
use App\Models\TenantInvoice;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\TenantBilling\TenantInvoiceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TenantInvoiceController extends Controller
{
    public function __construct(
        private readonly TenantInvoiceService $invoices,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', TenantInvoice::class);

        $invoices = $this->municipalScope
            ->tenantInvoices(TenantInvoice::query(), $this->currentUser())
            ->with(['tenant', 'leaseContract.housingUnit', 'payments'])
            ->latest('issue_date')
            ->paginate(20);

        return view('backoffice.tenant-invoices.index', compact('invoices'));
    }

    public function show(TenantInvoice $tenantInvoice): View
    {
        Gate::authorize('viewBackoffice', $tenantInvoice);
        $tenantInvoice->load(['tenant', 'tenantFinancialAccount', 'leaseContract.housingUnit', 'payments']);

        return view('backoffice.tenant-invoices.show', compact('tenantInvoice'));
    }

    public function store(GenerateTenantInvoiceRequest $request): RedirectResponse
    {
        Gate::authorize('generateBackoffice', TenantInvoice::class);
        $data = $request->validated();
        $actor = $this->authenticatedUser($request);
        $contract = $this->municipalScope
            ->contracts(Contract::query(), $actor)
            ->whereKey((int) $data['lease_contract_id'])
            ->firstOrFail();
        $invoice = $this->invoices->issueForContract($contract, $actor, $data);

        return to_route('backoffice.tenant-operations.invoices.show', $invoice)->with('success', 'Fatura operacional emitida.');
    }
}
