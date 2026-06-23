<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoadLotteryParticipantsRequest;
use App\Http\Requests\LockLotteryParticipantsRequest;
use App\Models\LotteryDraw;
use App\Services\Lottery\LotteryParticipantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LotteryParticipantController extends Controller
{
    public function __construct(private readonly LotteryParticipantService $participants) {}

    public function load(LoadLotteryParticipantsRequest $request, LotteryDraw $lotteryDraw): RedirectResponse
    {
        Gate::authorize('update', $lotteryDraw);

        $this->participants->loadFromDefinitiveList($lotteryDraw, $this->authenticatedUser($request));

        return to_route('backoffice.lottery-draws.show', $lotteryDraw)->with('success', 'Participantes carregados.');
    }

    public function lock(LockLotteryParticipantsRequest $request, LotteryDraw $lotteryDraw): RedirectResponse
    {
        Gate::authorize('update', $lotteryDraw);

        $this->participants->lock($lotteryDraw, $this->authenticatedUser($request));

        return to_route('backoffice.lottery-draws.show', $lotteryDraw)->with('success', 'Participantes bloqueados.');
    }
}
