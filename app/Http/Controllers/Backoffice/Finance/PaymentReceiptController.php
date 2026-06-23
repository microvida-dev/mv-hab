<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelFinanceRecordRequest;
use App\Models\LeasePayment;
use App\Models\PaymentReceipt;
use App\Services\Finance\PaymentReceiptService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReceiptController extends Controller
{
    public function __construct(private readonly PaymentReceiptService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', PaymentReceipt::class);
        $receipts = PaymentReceipt::query()->with(['tenant', 'leasePayment'])->latest()->paginate(25);

        return view('backoffice.finance.receipts.index', compact('receipts'));
    }

    public function show(PaymentReceipt $paymentReceipt): View
    {
        Gate::authorize('view', $paymentReceipt);
        $paymentReceipt->load(['tenant', 'leasePayment.allocations.rentInstallment', 'leaseContract']);

        return view('backoffice.finance.receipts.show', compact('paymentReceipt'));
    }

    public function generate(LeasePayment $leasePayment): RedirectResponse
    {
        Gate::authorize('create', PaymentReceipt::class);
        $receipt = $this->service->issue($leasePayment, $this->currentUser());

        return redirect()->route('backoffice.finance.receipts.show', $receipt)->with('success', 'Comprovativo interno emitido.');
    }

    public function download(PaymentReceipt $paymentReceipt): StreamedResponse
    {
        Gate::authorize('view', $paymentReceipt);

        $disk = $paymentReceipt->storage_disk;
        $path = $paymentReceipt->storage_path;

        abort_if($disk === null || $path === null || ! Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->download($path, $paymentReceipt->receipt_number.'.html');
    }

    public function cancel(CancelFinanceRecordRequest $request, PaymentReceipt $paymentReceipt): RedirectResponse
    {
        Gate::authorize('update', $paymentReceipt);
        $this->service->cancel($paymentReceipt, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Comprovativo cancelado.');
    }
}
