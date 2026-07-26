<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelFinanceRecordRequest;
use App\Http\Requests\StoreRegularizationAgreementRequest;
use App\Models\RegularizationAgreement;
use App\Models\TenantFinancialAccount;
use App\Services\Finance\RegularizationAgreementService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RegularizationAgreementController extends Controller
{
    public function __construct(
        private readonly RegularizationAgreementService $service,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', RegularizationAgreement::class);
        $agreements = $this->municipalScope
            ->regularizationAgreements(RegularizationAgreement::query(), $this->currentUser())
            ->with(['tenant', 'tenantFinancialAccount'])
            ->latest()
            ->paginate(25);

        return view('backoffice.finance.regularization-agreements.index', compact('agreements'));
    }

    public function create(): View
    {
        Gate::authorize('createBackoffice', RegularizationAgreement::class);
        $accounts = $this->municipalScope
            ->tenantFinancialAccounts(TenantFinancialAccount::query(), $this->currentUser())
            ->with(['tenant', 'arrears'])
            ->orderBy('account_number')
            ->get();

        return view('backoffice.finance.regularization-agreements.create', compact('accounts'));
    }

    public function store(StoreRegularizationAgreementRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', RegularizationAgreement::class);
        $actor = $this->authenticatedUser($request);
        $account = $this->municipalScope
            ->tenantFinancialAccounts(TenantFinancialAccount::query(), $actor)
            ->findOrFail($request->integer('tenant_financial_account_id'));
        $agreement = $this->service->store($account, $actor, $request->validated());

        return redirect()->route('backoffice.finance.regularization-agreements.show', $agreement)->with('success', 'Acordo criado.');
    }

    public function show(RegularizationAgreement $regularizationAgreement): View
    {
        Gate::authorize('viewBackoffice', $regularizationAgreement);
        $regularizationAgreement->load(['tenant', 'tenantFinancialAccount', 'arrears', 'installments']);

        return view('backoffice.finance.regularization-agreements.show', compact('regularizationAgreement'));
    }

    public function approve(RegularizationAgreement $regularizationAgreement): RedirectResponse
    {
        Gate::authorize('approveBackoffice', $regularizationAgreement);
        $this->service->approve($regularizationAgreement, $this->currentUser());

        return back()->with('success', 'Acordo aprovado.');
    }

    public function cancel(CancelFinanceRecordRequest $request, RegularizationAgreement $regularizationAgreement): RedirectResponse
    {
        Gate::authorize('cancelBackoffice', $regularizationAgreement);
        $this->service->cancel($regularizationAgreement, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Acordo cancelado.');
    }
}
