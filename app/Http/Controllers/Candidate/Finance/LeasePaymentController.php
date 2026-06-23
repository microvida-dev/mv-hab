<?php

namespace App\Http\Controllers\Candidate\Finance;

use App\Http\Controllers\Controller;
use App\Models\LeasePayment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class LeasePaymentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', LeasePayment::class);
        $payments = LeasePayment::query()->where('user_id', $this->currentUser()->id)->latest()->paginate(20);

        return view('candidate.finance.payments.index', compact('payments'));
    }

    public function show(LeasePayment $leasePayment): View
    {
        Gate::authorize('view', $leasePayment);
        $leasePayment->load(['tenantFinancialAccount', 'allocations.rentInstallment', 'receipt']);

        return view('candidate.finance.payments.show', compact('leasePayment'));
    }
}
