<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelFinanceRecordRequest;
use App\Http\Requests\StoreRegularizationAgreementRequest;
use App\Models\RegularizationAgreement;
use App\Models\TenantFinancialAccount;
use App\Services\Finance\RegularizationAgreementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RegularizationAgreementController extends Controller
{
    public function __construct(private readonly RegularizationAgreementService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', RegularizationAgreement::class);
        $agreements = RegularizationAgreement::query()->with(['tenant', 'tenantFinancialAccount'])->latest()->paginate(25);

        return view('backoffice.finance.regularization-agreements.index', compact('agreements'));
    }

    public function create(): View
    {
        Gate::authorize('create', RegularizationAgreement::class);
        $accounts = TenantFinancialAccount::query()->with(['tenant', 'arrears'])->orderBy('account_number')->get();

        return view('backoffice.finance.regularization-agreements.create', compact('accounts'));
    }

    public function store(StoreRegularizationAgreementRequest $request): RedirectResponse
    {
        Gate::authorize('create', RegularizationAgreement::class);
        $account = TenantFinancialAccount::query()->findOrFail($request->integer('tenant_financial_account_id'));
        $agreement = $this->service->store($account, $this->authenticatedUser($request), $request->validated());

        return redirect()->route('backoffice.finance.regularization-agreements.show', $agreement)->with('success', 'Acordo criado.');
    }

    public function show(RegularizationAgreement $regularizationAgreement): View
    {
        Gate::authorize('view', $regularizationAgreement);
        $regularizationAgreement->load(['tenant', 'tenantFinancialAccount', 'arrears', 'installments']);

        return view('backoffice.finance.regularization-agreements.show', compact('regularizationAgreement'));
    }

    public function approve(RegularizationAgreement $regularizationAgreement): RedirectResponse
    {
        Gate::authorize('update', $regularizationAgreement);
        $this->service->approve($regularizationAgreement, $this->currentUser());

        return back()->with('success', 'Acordo aprovado.');
    }

    public function cancel(CancelFinanceRecordRequest $request, RegularizationAgreement $regularizationAgreement): RedirectResponse
    {
        Gate::authorize('update', $regularizationAgreement);
        $this->service->cancel($regularizationAgreement, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Acordo cancelado.');
    }
}
