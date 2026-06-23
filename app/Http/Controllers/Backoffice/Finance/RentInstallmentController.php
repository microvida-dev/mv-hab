<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Enums\RentInstallmentStatus;
use App\Http\Controllers\Controller;
use App\Models\RentInstallment;
use App\Models\TenantFinancialAccount;
use App\Services\Finance\FinancialTransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RentInstallmentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', RentInstallment::class);
        $installments = RentInstallment::query()->with(['tenant', 'tenantFinancialAccount'])->latest('due_date')->paginate(25);

        return view('backoffice.finance.installments.index', compact('installments'));
    }

    public function show(RentInstallment $rentInstallment): View
    {
        Gate::authorize('view', $rentInstallment);
        $rentInstallment->load(['tenant', 'tenantFinancialAccount', 'leaseContract', 'allocations.leasePayment', 'arrear']);

        return view('backoffice.finance.installments.show', compact('rentInstallment'));
    }

    public function issue(RentInstallment $rentInstallment): RedirectResponse
    {
        Gate::authorize('update', $rentInstallment);
        $rentInstallment->forceFill(['status' => RentInstallmentStatus::Issued, 'issued_at' => now()])->save();

        return back()->with('success', 'Prestação emitida.');
    }

    public function waive(RentInstallment $rentInstallment, FinancialTransactionService $transactions): RedirectResponse
    {
        Gate::authorize('update', $rentInstallment);
        $rentInstallment->forceFill([
            'status' => RentInstallmentStatus::Waived,
            'amount_waived' => $rentInstallment->amount_outstanding,
            'amount_outstanding' => 0,
        ])->save();

        $account = $rentInstallment->tenantFinancialAccount;
        abort_unless($account instanceof TenantFinancialAccount, 500);

        $transactions->recalculateAccount($account);

        return back()->with('success', 'Prestação dispensada.');
    }
}
