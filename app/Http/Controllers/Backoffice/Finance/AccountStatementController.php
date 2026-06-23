<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Models\TenantFinancialAccount;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class AccountStatementController extends Controller
{
    public function show(TenantFinancialAccount $tenantFinancialAccount): View
    {
        Gate::authorize('view', $tenantFinancialAccount);
        $tenantFinancialAccount->load(['tenant', 'leaseContract', 'financialTransactions' => fn ($query) => $query->latest('occurred_at')->limit(100)]);

        return view('backoffice.finance.statements.show', compact('tenantFinancialAccount'));
    }
}
