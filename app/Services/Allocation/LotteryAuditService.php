<?php

namespace App\Services\Allocation;

use App\Models\LotteryRun;
use Illuminate\Support\Str;

class LotteryAuditService
{
    public function seed(?string $provided = null): string
    {
        return $provided ?: now()->format('YmdHis').'-'.Str::upper(Str::random(12));
    }

    public function randomValue(string $seed, string $participantNumber): string
    {
        return hash('sha256', $seed.':'.$participantNumber);
    }

    /**
     * @return array<string|int, mixed>
     */
    public function lockPayload(LotteryRun $lotteryRun): array
    {
        $payload = [
            'lottery_run_id' => $lotteryRun->id,
            'seed' => $lotteryRun->seed,
            'seed_source' => $lotteryRun->seed_source,
            'algorithm' => $lotteryRun->algorithm,
            'participants' => $lotteryRun->participants()->orderBy('participant_number')->pluck('participant_number')->all(),
            'results' => $lotteryRun->drawResults()->orderBy('draw_order')->get(['draw_order', 'result_type', 'random_value'])->toArray(),
        ];

        return [
            'payload' => $payload,
            'hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
        ];
    }
}
