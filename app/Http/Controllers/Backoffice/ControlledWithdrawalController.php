<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\ControlledWithdrawal;
use App\Services\ApplicationActions\ControlledWithdrawalService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ControlledWithdrawalController extends Controller
{
    public function __construct(
        private readonly ControlledWithdrawalService $withdrawals,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', ControlledWithdrawal::class);

        return view('backoffice.withdrawals.index', [
            'withdrawals' => $this->municipalScope
                ->controlledWithdrawals(
                    ControlledWithdrawal::query(),
                    $this->authenticatedUser($request),
                )
                ->with(['application', 'user'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(ControlledWithdrawal $controlledWithdrawal): View
    {
        Gate::authorize('viewBackoffice', $controlledWithdrawal);

        return view('backoffice.withdrawals.show', ['withdrawal' => $controlledWithdrawal]);
    }

    public function process(Request $request, ControlledWithdrawal $controlledWithdrawal): RedirectResponse
    {
        Gate::authorize('processBackoffice', $controlledWithdrawal);
        $this->withdrawals->markReviewed(
            $controlledWithdrawal,
            $this->authenticatedUser($request),
        );

        return back()->with('success', 'Desistência revista.');
    }
}
