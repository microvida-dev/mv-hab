<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelFinanceRecordRequest;
use App\Models\LeasePayment;
use App\Models\PaymentReceipt;
use App\Services\Finance\PaymentReceiptService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReceiptController extends Controller
{
    public function __construct(
        private readonly PaymentReceiptService $service,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', PaymentReceipt::class);
        $receipts = $this->municipalScope
            ->paymentReceipts(PaymentReceipt::query(), $this->currentUser())
            ->with(['tenant', 'leasePayment'])
            ->latest()
            ->paginate(25);

        return view('backoffice.finance.receipts.index', compact('receipts'));
    }

    public function show(PaymentReceipt $paymentReceipt): View
    {
        Gate::authorize('viewBackoffice', $paymentReceipt);
        $paymentReceipt->load(['tenant', 'leasePayment.allocations.rentInstallment', 'leaseContract']);

        return view('backoffice.finance.receipts.show', compact('paymentReceipt'));
    }

    public function generate(LeasePayment $leasePayment): RedirectResponse
    {
        Gate::authorize('generateBackoffice', [PaymentReceipt::class, $leasePayment]);
        $receipt = $this->service->issue($leasePayment, $this->currentUser());

        return redirect()->route('backoffice.finance.receipts.show', $receipt)->with('success', 'Comprovativo interno emitido.');
    }

    public function download(PaymentReceipt $paymentReceipt): StreamedResponse
    {
        Gate::authorize('downloadBackoffice', $paymentReceipt);

        return $this->service->download($paymentReceipt, $this->currentUser());
    }

    public function cancel(CancelFinanceRecordRequest $request, PaymentReceipt $paymentReceipt): RedirectResponse
    {
        Gate::authorize('cancelBackoffice', $paymentReceipt);
        $this->service->cancel($paymentReceipt, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Comprovativo cancelado.');
    }
}
