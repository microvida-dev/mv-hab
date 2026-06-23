<?php

namespace Tests\Unit\Lottery;

use App\Models\LotteryParticipant;
use App\Services\Lottery\AuditableLotteryEngine;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class AuditableLotteryEngineTest extends TestCase
{
    public function test_same_seed_and_participants_generate_same_order_and_hash(): void
    {
        $participants = new Collection([
            $this->participant(1, 11, 101, 'DRAW-000001'),
            $this->participant(2, 12, 102, 'DRAW-000002'),
            $this->participant(3, 13, 103, 'DRAW-000003'),
        ]);

        $engine = new AuditableLotteryEngine;

        $first = $engine->draw($participants, 'SPRINT-25-SEED');
        $second = $engine->draw($participants, 'SPRINT-25-SEED');

        $this->assertSame($first['result_hash'], $second['result_hash']);
        $this->assertSame($first['ordered'], $second['ordered']);
        $this->assertSame('sha256(seed:participant)', $first['algorithm']);
    }

    private function participant(int $id, int $applicationId, int $userId, string $number): LotteryParticipant
    {
        $participant = new LotteryParticipant;
        $participant->forceFill([
            'id' => $id,
            'application_id' => $applicationId,
            'user_id' => $userId,
            'participant_number' => $number,
        ]);

        return $participant;
    }
}
