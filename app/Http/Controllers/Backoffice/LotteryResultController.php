<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\LotteryDraw;
use App\Services\Lottery\LotteryResultService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class LotteryResultController extends Controller
{
    public function __construct(private readonly LotteryResultService $results) {}

    public function index(LotteryDraw $lotteryDraw): View
    {
        Gate::authorize('view', $lotteryDraw);

        return view('backoffice.lottery-draws.results', [
            'lotteryDraw' => $lotteryDraw,
            'results' => $this->results->orderedResults($lotteryDraw),
        ]);
    }
}
