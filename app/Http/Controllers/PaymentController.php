<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    public function index(): View
    {
        $payments = Payment::query()
            ->with(['contract.citizen', 'contract.housingUnit'])
            ->latest()
            ->paginate(15);

        return view('payments.index', compact('payments'));
    }

    public function create(): View
    {
        $contracts = Contract::query()
            ->with(['citizen:id,name', 'housingUnit:id,code'])
            ->latest()
            ->get(['id', 'citizen_id', 'housing_unit_id']);
        $statuses = PaymentStatus::options();

        return view('payments.create', compact('contracts', 'statuses'));
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (($validated['status'] ?? null) === PaymentStatus::Paid->value && empty($validated['paid_at'])) {
            $validated['paid_at'] = now();
        }

        if (($validated['status'] ?? null) !== PaymentStatus::Paid->value) {
            $validated['paid_at'] = null;
        }

        Payment::create($validated);

        return to_route('payments.index')
            ->with('success', 'Pagamento criado com sucesso.');
    }

    public function show(Payment $payment): View
    {
        $payment->load(['contract.citizen', 'contract.housingUnit']);

        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment): View
    {
        $contracts = Contract::query()
            ->with(['citizen:id,name', 'housingUnit:id,code'])
            ->latest()
            ->get(['id', 'citizen_id', 'housing_unit_id']);
        $statuses = PaymentStatus::options();

        return view('payments.edit', compact('payment', 'contracts', 'statuses'));
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validated();

        if (($validated['status'] ?? null) === PaymentStatus::Paid->value && empty($validated['paid_at'])) {
            $validated['paid_at'] = $payment->paid_at ?? now();
        }

        if (($validated['status'] ?? null) !== PaymentStatus::Paid->value) {
            $validated['paid_at'] = null;
        }

        $payment->update($validated);

        return to_route('payments.index')
            ->with('success', 'Pagamento atualizado com sucesso.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $payment->delete();

        return to_route('payments.index')
            ->with('success', 'Pagamento eliminado com sucesso.');
    }
}
