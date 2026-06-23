<?php

namespace App\Services\Lottery;

use App\Models\LotteryDraw;
use App\Models\LotteryResult;
use Illuminate\Database\Eloquent\Collection;

class LotteryResultService
{
    /**
     * @return Collection<int, LotteryResult>
     */
    public function orderedResults(LotteryDraw $draw): Collection
    {
        return $draw->results()
            ->with(['candidate', 'application', 'assignedHousingUnit'])
            ->orderBy('draw_order')
            ->get();
    }
}
