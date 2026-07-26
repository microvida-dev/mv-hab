<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\AllocatePaymentRequest;
use App\Http\Requests\ReverseLeasePaymentRequest;
use App\Http\Requests\StoreLeasePaymentRequest;
use App\Models\LeasePayment;
use App\Models\RentInstallment;
use App\Models\TenantFinancialAccount;
use App\Services\Finance\LeasePaymentService;
use App\Services\Finance\PaymentAllocationService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LeasePaymentController extends Controller
{
    public function __construct(
        private readonly LeasePaymentService $payments,
        private readonly PaymentAllocationService $allocations,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', LeasePayment::class);
        $payments = $this->municipalScope
            ->leasePayments(LeasePayment::query(), $this->currentUser())
            ->with(['tenant', 'tenantFinancialAccount'])
            ->latest()
            ->paginate(25);

        return view('backoffice.finance.payments.index', compact('payments'));
    }

    public function create(): View
    {
        Gate::authorize('createBackoffice', LeasePayment::class);
        $accounts = $this->municipalScope
            ->tenantFinancialAccounts(TenantFinancialAccount::query(), $this->currentUser())
            ->with('tenant')
            ->orderBy('account_number')
            ->get();

        return view('backoffice.finance.payments.create', compact('accounts'));
    }

    public function store(StoreLeasePaymentRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', LeasePayment::class);
        $actor = $this->authenticatedUser($request);
        $account = $this->municipalScope
            ->tenantFinancialAccounts(TenantFinancialAccount::query(), $actor)
            ->findOrFail($request->integer('tenant_financial_account_id'));
        $payment = $this->payments->store($account, $actor, $request->validated());

        return redirect()->route('backoffice.finance.payments.show', $payment)->with('success', 'Pagamento registado.');
    }

    public function show(LeasePayment $leasePayment): View
    {
        Gate::authorize('viewBackoffice', $leasePayment);
        $leasePayment->load(['tenant', 'tenantFinancialAccount.rentInstallments', 'leaseContract', 'allocations.rentInstallment', 'receipt']);

        return view('backoffice.finance.payments.show', compact('leasePayment'));
    }

    public function confirm(LeasePayment $leasePayment): RedirectResponse
    {
        Gate::authorize('confirmBackoffice', $leasePayment);
        $this->payments->confirm($leasePayment, $this->currentUser());

        return back()->with('success', 'Pagamento confirmado.');
    }

    public function reverse(ReverseLeasePaymentRequest $request, LeasePayment $leasePayment): RedirectResponse
    {
        Gate::authorize('reverseBackoffice', $leasePayment);
        $this->payments->reverse($leasePayment, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Pagamento estornado.');
    }

    public function allocate(AllocatePaymentRequest $request, LeasePayment $leasePayment): RedirectResponse
    {
        Gate::authorize('allocateBackoffice', $leasePayment);

        if ($request->boolean('allocate_oldest') || ! $request->validated('rent_installment_id')) {
            $this->allocations->allocateOldest($leasePayment, $this->authenticatedUser($request));
        } else {
            $installment = $this->municipalScope
                ->rentInstallments(RentInstallment::query(), $this->authenticatedUser($request))
                ->findOrFail($request->integer('rent_installment_id'));
            $this->allocations->allocate($leasePayment, $installment, $this->authenticatedUser($request), $request->validated('amount'));
        }

        return back()->with('success', 'Pagamento imputado.');
    }
}
