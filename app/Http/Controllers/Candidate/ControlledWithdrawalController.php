<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelControlledWithdrawalRequest;
use App\Http\Requests\ConfirmControlledWithdrawalRequest;
use App\Http\Requests\StoreControlledWithdrawalRequest;
use App\Models\Application;
use App\Models\ControlledWithdrawal;
use App\Services\ApplicationActions\ControlledWithdrawalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ControlledWithdrawalController extends Controller
{
    public function __construct(private readonly ControlledWithdrawalService $service) {}

    public function create(Application $application): View
    {
        Gate::authorize('create', [ControlledWithdrawal::class, $application]);

        return view('candidate.withdrawals.create', compact('application'));
    }

    public function store(StoreControlledWithdrawalRequest $request, Application $application): RedirectResponse
    {
        Gate::authorize('create', [ControlledWithdrawal::class, $application]);
        $withdrawal = $this->service->create(
            $application,
            $this->authenticatedUser($request),
            (string) $request->validated('reason'),
            (bool) $request->boolean('consequence_acknowledged'),
        );

        return to_route('candidate.withdrawals.show', $withdrawal)->with('success', 'Pedido de desistência criado.');
    }

    public function show(ControlledWithdrawal $controlledWithdrawal): View
    {
        Gate::authorize('view', $controlledWithdrawal);

        return view('candidate.withdrawals.show', ['withdrawal' => $controlledWithdrawal]);
    }

    public function confirm(ConfirmControlledWithdrawalRequest $request, ControlledWithdrawal $controlledWithdrawal): RedirectResponse
    {
        Gate::authorize('update', $controlledWithdrawal);
        $this->service->confirm($controlledWithdrawal, $this->authenticatedUser($request));

        return to_route('candidate.applications.index')->with('success', 'Desistência confirmada.');
    }

    public function cancel(CancelControlledWithdrawalRequest $request, ControlledWithdrawal $controlledWithdrawal): RedirectResponse
    {
        Gate::authorize('update', $controlledWithdrawal);
        $this->service->cancel($controlledWithdrawal, $this->authenticatedUser($request));

        return to_route('candidate.applications.show', $controlledWithdrawal->application)->with('success', 'Desistência cancelada.');
    }
}
