<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyRankingUpdateRequest;
use App\Models\LotteryDraw;
use App\Models\RankingUpdateRun;
use App\Services\Lottery\RankingUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RankingUpdateRunController extends Controller
{
    public function __construct(private readonly RankingUpdateService $rankings) {}

    public function apply(ApplyRankingUpdateRequest $request, LotteryDraw $lotteryDraw): RedirectResponse
    {
        Gate::authorize('create', RankingUpdateRun::class);

        $this->rankings->apply($lotteryDraw, $this->authenticatedUser($request));

        return to_route('backoffice.lottery-draws.show', $lotteryDraw)->with('success', 'Ranking pós-sorteio registado.');
    }
}
