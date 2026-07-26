<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Contract;
use App\Models\Payment;
use App\Services\Finance\LegacyPaymentService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly LegacyPaymentService $payments,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', Payment::class);

        $payments = $this->municipalScope
            ->payments(Payment::query(), $this->currentUser())
            ->with(['contract.citizen', 'contract.housingUnit'])
            ->latest()
            ->paginate(15);

        return view('payments.index', compact('payments'));
    }

    public function create(): View
    {
        Gate::authorize('createBackoffice', Payment::class);

        $contracts = $this->municipalScope
            ->contracts(Contract::query(), $this->currentUser())
            ->with(['citizen:id,name', 'housingUnit:id,code'])
            ->latest()
            ->get(['id', 'citizen_id', 'housing_unit_id']);
        $statuses = PaymentStatus::options();

        return view('payments.create', compact('contracts', 'statuses'));
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', Payment::class);
        $validated = $request->validated();
        $this->municipalScope
            ->contracts(Contract::query(), $this->authenticatedUser($request))
            ->findOrFail((int) $validated['contract_id']);

        $this->payments->create($validated, $this->authenticatedUser($request));

        return to_route('payments.index')
            ->with('success', 'Pagamento criado com sucesso.');
    }

    public function show(Payment $payment): View
    {
        Gate::authorize('viewBackoffice', $payment);
        $payment->load(['contract.citizen', 'contract.housingUnit']);

        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment): View
    {
        Gate::authorize('updateBackoffice', $payment);

        $contracts = $this->municipalScope
            ->contracts(Contract::query(), $this->currentUser())
            ->with(['citizen:id,name', 'housingUnit:id,code'])
            ->latest()
            ->get(['id', 'citizen_id', 'housing_unit_id']);
        $statuses = PaymentStatus::options();

        return view('payments.edit', compact('payment', 'contracts', 'statuses'));
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $payment);
        $validated = $request->validated();
        $this->municipalScope
            ->contracts(Contract::query(), $this->authenticatedUser($request))
            ->findOrFail((int) $validated['contract_id']);

        $this->payments->update($payment, $validated, $this->authenticatedUser($request));

        return to_route('payments.index')
            ->with('success', 'Pagamento atualizado com sucesso.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        Gate::authorize('deleteBackoffice', $payment);
        $this->payments->delete($payment, $this->currentUser());

        return to_route('payments.index')
            ->with('success', 'Pagamento eliminado com sucesso.');
    }
}
