<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Models\RentInstallment;
use App\Services\Finance\RentInstallmentService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RentInstallmentController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly RentInstallmentService $service,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', RentInstallment::class);
        $installments = $this->municipalScope
            ->rentInstallments(RentInstallment::query(), $this->currentUser())
            ->with(['tenant', 'tenantFinancialAccount'])
            ->latest('due_date')
            ->paginate(25);

        return view('backoffice.finance.installments.index', compact('installments'));
    }

    public function show(RentInstallment $rentInstallment): View
    {
        Gate::authorize('viewBackoffice', $rentInstallment);
        $rentInstallment->load(['tenant', 'tenantFinancialAccount', 'leaseContract', 'allocations.leasePayment', 'arrear']);

        return view('backoffice.finance.installments.show', compact('rentInstallment'));
    }

    public function issue(RentInstallment $rentInstallment): RedirectResponse
    {
        Gate::authorize('issueBackoffice', $rentInstallment);
        $this->service->issue($rentInstallment, $this->currentUser());

        return back()->with('success', 'Prestação emitida.');
    }

    public function waive(RentInstallment $rentInstallment): RedirectResponse
    {
        Gate::authorize('waiveBackoffice', $rentInstallment);
        $this->service->waive($rentInstallment, $this->currentUser());

        return back()->with('success', 'Prestação dispensada.');
    }
}
