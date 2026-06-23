<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarkContractDepositPaidRequest;
use App\Http\Requests\WaiveContractDepositRequest;
use App\Models\ContractDeposit;
use App\Services\Contracts\ContractDepositService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContractDepositController extends Controller
{
    public function __construct(private readonly ContractDepositService $service) {}

    public function show(ContractDeposit $contractDeposit): View
    {
        Gate::authorize('view', $contractDeposit);
        $contractDeposit->load(['leaseContract.candidate', 'leaseContract.housingUnit']);

        return view('backoffice.contracts.deposits.show', compact('contractDeposit'));
    }

    public function markRequested(Request $request, ContractDeposit $contractDeposit): RedirectResponse
    {
        Gate::authorize('update', $contractDeposit);
        $this->service->markRequested($contractDeposit, $this->authenticatedUser($request));

        return back()->with('success', 'Caução marcada como solicitada.');
    }

    public function markPaid(MarkContractDepositPaidRequest $request, ContractDeposit $contractDeposit): RedirectResponse
    {
        Gate::authorize('update', $contractDeposit);
        $this->service->markPaid($contractDeposit, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Caução registada como paga manualmente.');
    }

    public function markWaived(WaiveContractDepositRequest $request, ContractDeposit $contractDeposit): RedirectResponse
    {
        Gate::authorize('update', $contractDeposit);
        $this->service->waive($contractDeposit, $this->authenticatedUser($request), $request->validated('reason'), $request->validated('internal_notes'));

        return back()->with('success', 'Caução dispensada.');
    }

    public function cancel(Request $request, ContractDeposit $contractDeposit): RedirectResponse
    {
        Gate::authorize('update', $contractDeposit);
        $this->service->cancel($contractDeposit, $this->authenticatedUser($request), $request->input('reason'));

        return back()->with('success', 'Caução cancelada.');
    }
}
