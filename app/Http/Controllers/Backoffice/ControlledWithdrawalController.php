<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\ControlledWithdrawal;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ControlledWithdrawalController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', ControlledWithdrawal::class);

        return view('backoffice.withdrawals.index', [
            'withdrawals' => ControlledWithdrawal::query()->with(['application', 'user'])->latest()->paginate(20),
        ]);
    }

    public function show(ControlledWithdrawal $controlledWithdrawal): View
    {
        Gate::authorize('view', $controlledWithdrawal);

        return view('backoffice.withdrawals.show', ['withdrawal' => $controlledWithdrawal]);
    }

    public function process(Request $request, ControlledWithdrawal $controlledWithdrawal): RedirectResponse
    {
        Gate::authorize('update', $controlledWithdrawal);
        $controlledWithdrawal->forceFill([
            'processed_by' => $this->authenticatedUser($request)->id,
        ])->save();

        return back()->with('success', 'Desistência revista.');
    }
}
