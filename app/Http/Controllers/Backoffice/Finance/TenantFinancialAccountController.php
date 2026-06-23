<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\TenantFinancialAccount;
use App\Services\Finance\TenantFinancialAccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TenantFinancialAccountController extends Controller
{
    public function __construct(private readonly TenantFinancialAccountService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', TenantFinancialAccount::class);

        $accounts = TenantFinancialAccount::query()
            ->with(['tenant', 'leaseContract.housingUnit'])
            ->latest()
            ->paginate(20);

        return view('backoffice.finance.accounts.index', compact('accounts'));
    }

    public function show(TenantFinancialAccount $tenantFinancialAccount): View
    {
        Gate::authorize('view', $tenantFinancialAccount);

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
        Gate::authorize('create', TenantFinancialAccount::class);

        $data = $request->validate([
            'lease_contract_id' => ['required', 'integer', 'exists:contracts,id'],
        ]);

        $contract = Contract::query()->whereKey((int) $data['lease_contract_id'])->firstOrFail();
        $account = $this->service->ensureForContract($contract, $this->authenticatedUser($request));

        return redirect()->route('backoffice.finance.accounts.show', $account)->with('success', 'Conta financeira criada ou localizada.');
    }
}
