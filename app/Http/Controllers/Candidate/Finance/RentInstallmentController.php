<?php

namespace App\Http\Controllers\Candidate\Finance;

use App\Http\Controllers\Controller;
use App\Models\RentInstallment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class RentInstallmentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', RentInstallment::class);
        $installments = RentInstallment::query()->where('user_id', $this->currentUser()->id)->latest('due_date')->paginate(20);

        return view('candidate.finance.installments.index', compact('installments'));
    }

    public function show(RentInstallment $rentInstallment): View
    {
        Gate::authorize('view', $rentInstallment);
        $rentInstallment->load(['tenantFinancialAccount', 'leaseContract', 'allocations.leasePayment']);

        return view('candidate.finance.installments.show', compact('rentInstallment'));
    }
}
