<?php

namespace App\Http\Controllers\Candidate\Finance;

use App\Http\Controllers\Controller;
use App\Models\PaymentReceipt;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReceiptController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', PaymentReceipt::class);
        $receipts = PaymentReceipt::query()->where('user_id', $this->currentUser()->id)->latest()->paginate(20);

        return view('candidate.finance.receipts.index', compact('receipts'));
    }

    public function show(PaymentReceipt $paymentReceipt): View
    {
        Gate::authorize('view', $paymentReceipt);
        $paymentReceipt->load(['leasePayment.allocations.rentInstallment', 'leaseContract']);

        return view('candidate.finance.receipts.show', compact('paymentReceipt'));
    }

    public function download(PaymentReceipt $paymentReceipt): StreamedResponse
    {
        Gate::authorize('view', $paymentReceipt);

        $disk = $paymentReceipt->storage_disk;
        $path = $paymentReceipt->storage_path;

        abort_if($disk === null || $path === null || ! Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->download($path, $paymentReceipt->receipt_number.'.html');
    }
}
