<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportPaymentsRequest;
use App\Models\PaymentImportBatch;
use App\Services\Finance\PaymentImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PaymentImportController extends Controller
{
    public function __construct(private readonly PaymentImportService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', PaymentImportBatch::class);
        $batches = PaymentImportBatch::query()->latest()->paginate(20);

        return view('backoffice.finance.imports.index', compact('batches'));
    }

    public function create(): View
    {
        Gate::authorize('create', PaymentImportBatch::class);

        return view('backoffice.finance.imports.create');
    }

    public function store(ImportPaymentsRequest $request): RedirectResponse
    {
        Gate::authorize('create', PaymentImportBatch::class);
        $batch = $this->service->store($request->file('file'), $this->authenticatedUser($request), $request->validated('notes'));

        return redirect()->route('backoffice.finance.imports.show', $batch)->with('success', 'Lote importado para validação.');
    }

    public function show(PaymentImportBatch $paymentImportBatch): View
    {
        Gate::authorize('view', $paymentImportBatch);
        $paymentImportBatch->load('rows');

        return view('backoffice.finance.imports.show', compact('paymentImportBatch'));
    }

    public function process(PaymentImportBatch $paymentImportBatch): RedirectResponse
    {
        Gate::authorize('update', $paymentImportBatch);
        $this->service->process($paymentImportBatch, $this->currentUser());

        return back()->with('success', 'Lote processado.');
    }
}
