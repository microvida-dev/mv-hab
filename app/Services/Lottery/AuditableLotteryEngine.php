<?php

namespace App\Services\Lottery;

use App\Models\LotteryParticipant;
use Illuminate\Support\Collection;

class AuditableLotteryEngine
{
    /**
     * @param  Collection<int, LotteryParticipant>  $participants
     * @return array{seed:string, seed_hash:string, algorithm:string, ordered:list<array{participant_id:int, application_id:int, user_id:int, participant_number:string, drawn_position:int, random_value:string}>, result_hash:string}
     */
    public function draw(Collection $participants, ?string $seed = null, string $algorithm = 'sha256(seed:participant)'): array
    {
        $seed ??= bin2hex(random_bytes(32));

        $ordered = array_values($participants
            ->sortBy([
                ['participant_number', 'asc'],
                ['application_id', 'asc'],
            ])
            ->map(function (LotteryParticipant $participant) use ($seed): array {
                return [
                    'participant_id' => (int) $participant->id,
                    'application_id' => (int) $participant->application_id,
                    'user_id' => (int) $participant->user_id,
                    'participant_number' => (string) $participant->participant_number,
                    'random_value' => hash('sha256', $seed.'|'.$participant->participant_number.'|'.$participant->application_id),
                ];
            })
            ->sortBy('random_value')
            ->values()
            ->map(function (array $payload, int $index): array {
                $payload['drawn_position'] = $index + 1;

                return $payload;
            })
            ->all());

        $resultPayload = [
            'algorithm' => $algorithm,
            'seed_hash' => hash('sha256', $seed),
            'ordered' => $ordered,
        ];

        return [
            'seed' => $seed,
            'seed_hash' => $resultPayload['seed_hash'],
            'algorithm' => $algorithm,
            'ordered' => $ordered,
            'result_hash' => hash('sha256', json_encode($resultPayload, JSON_THROW_ON_ERROR)),
        ];
    }
}
