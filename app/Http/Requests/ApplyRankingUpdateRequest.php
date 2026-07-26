<?php

namespace App\Http\Requests;

use App\Models\LotteryDraw;
use App\Models\RankingUpdateRun;
use Illuminate\Foundation\Http\FormRequest;

class ApplyRankingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $draw = $this->route('lotteryDraw');

        return $draw instanceof LotteryDraw
            && ($this->user()?->can(
                'createBackoffice',
                [RankingUpdateRun::class, $draw],
            ) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
