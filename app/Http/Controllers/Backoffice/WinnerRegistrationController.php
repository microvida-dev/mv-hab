<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterWinnerRequest;
use App\Models\LotteryResult;
use App\Models\WinnerRegistration;
use App\Services\Lottery\WinnerRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WinnerRegistrationController extends Controller
{
    public function __construct(private readonly WinnerRegistrationService $winners) {}

    public function store(RegisterWinnerRequest $request, LotteryResult $lotteryResult): RedirectResponse
    {
        Gate::authorize('create', WinnerRegistration::class);

        $winner = $this->winners->register($lotteryResult, $this->authenticatedUser($request), $request->validated());

        return to_route('backoffice.lottery-draws.show', $winner->lottery_run_id)->with('success', 'Vencedor registado.');
    }
}
