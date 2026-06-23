<?php

namespace App\Http\Controllers\Candidate\Finance;

use App\Http\Controllers\Controller;
use App\Models\TenantFinancialAccount;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class FinancialAccountController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', TenantFinancialAccount::class);

        $accounts = TenantFinancialAccount::query()
            ->where('user_id', $this->currentUser()->id)
            ->with(['leaseContract.housingUnit'])
            ->latest()
            ->get();

        return view('candidate.finance.index', compact('accounts'));
    }

    public function show(TenantFinancialAccount $tenantFinancialAccount): View
    {
        Gate::authorize('view', $tenantFinancialAccount);
        $tenantFinancialAccount->load(['leaseContract.housingUnit', 'rentInstallments', 'leasePayments', 'arrears', 'regularizationAgreements']);

        return view('candidate.finance.statements.show', compact('tenantFinancialAccount'));
    }
}
