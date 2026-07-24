<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\TenantFinancialAccount;
use App\Services\Finance\TenantFinancialAccountService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TenantFinancialAccountController extends Controller
{
    public function __construct(
        private readonly TenantFinancialAccountService $service,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', TenantFinancialAccount::class);

        $accounts = $this->municipalScope
            ->tenantFinancialAccounts(TenantFinancialAccount::query(), $this->currentUser())
            ->with(['tenant', 'leaseContract.housingUnit'])
            ->latest()
            ->paginate(20);

        return view('backoffice.finance.accounts.index', compact('accounts'));
    }

    public function show(TenantFinancialAccount $tenantFinancialAccount): View
    {
        Gate::authorize('viewBackoffice', $tenantFinancialAccount);

        $tenantFinancialAccount->load([
            'tenant',
            'leaseContract.housingUnit',
            'activeSchedule',
            'rentInstallments' => fn ($query) => $query->latest('due_date')->limit(12),
            'leasePayments' => fn ($query) => $query->latest()->limit(10),
            'arrears' => fn ($query) => $query->latest()->limit(10),
        ]);

        return view('backoffice.finance.accounts.show', compact('tenantFinancialAccount'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', TenantFinancialAccount::class);

        $data = $request->validate([
            'lease_contract_id' => ['required', 'integer', 'exists:contracts,id'],
        ]);

        $actor = $this->authenticatedUser($request);
        $contract = $this->municipalScope
            ->contracts(Contract::query(), $actor)
            ->whereKey((int) $data['lease_contract_id'])
            ->firstOrFail();
        $account = $this->service->ensureForContract($contract, $actor);

        return redirect()->route('backoffice.finance.accounts.show', $account)->with('success', 'Conta financeira criada ou localizada.');
    }
}
