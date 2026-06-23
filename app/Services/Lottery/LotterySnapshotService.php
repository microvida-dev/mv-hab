<?php

namespace App\Services\Lottery;

use App\Models\LotteryParticipant;
use Illuminate\Support\Collection;

class LotterySnapshotService
{
    /**
     * @param  Collection<int, LotteryParticipant>  $participants
     * @return list<array{participant_id:int, application_id:int, user_id:int, participant_number:string, rank_position:int|null, previous_score:string|null, status:string}>
     */
    public function participantPayload(Collection $participants): array
    {
        return array_values($participants
            ->sortBy([
                ['participant_number', 'asc'],
                ['application_id', 'asc'],
            ])
            ->map(fn (LotteryParticipant $participant): array => [
                'participant_id' => (int) $participant->id,
                'application_id' => (int) $participant->application_id,
                'user_id' => (int) $participant->user_id,
                'participant_number' => (string) $participant->participant_number,
                'rank_position' => $participant->rank_position === null ? null : (int) $participant->rank_position,
                'previous_score' => $participant->previous_score === null ? null : (string) $participant->previous_score,
                'status' => $participant->status->value,
            ])
            ->values()
            ->all());
    }

    /**
     * @param  Collection<int, LotteryParticipant>  $participants
     */
    public function hashParticipants(Collection $participants): string
    {
        return hash('sha256', json_encode($this->participantPayload($participants), JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function hashPayload(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
