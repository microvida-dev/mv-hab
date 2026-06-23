<?php

namespace Database\Seeders;

use App\Enums\LotteryDrawStatus;
use App\Enums\LotteryParticipantStatus;
use App\Enums\LotteryResultStatus;
use App\Enums\LotteryResultType;
use App\Models\DrawAttendance;
use App\Models\DrawConvocation;
use App\Models\KeyHandoverAppointment;
use App\Models\LotteryDraw;
use App\Models\LotteryParticipant;
use App\Models\LotteryResult;
use App\Models\PostDrawReport;
use App\Models\RankingUpdateRun;
use App\Models\TenantTransition;
use App\Models\WinnerRegistration;
use Illuminate\Database\Seeder;

class LotteryClosureDemoSeeder extends Seeder
{
    public function run(): void
    {
        $draw = LotteryDraw::factory()->create([
            'status' => LotteryDrawStatus::Validated->value,
            'participants_hash' => hash('sha256', 'demo-participants'),
            'result_hash' => hash('sha256', 'demo-result'),
            'validated_at' => now(),
        ]);

        $participant = LotteryParticipant::factory()->create([
            'lottery_run_id' => $draw->id,
            'status' => LotteryParticipantStatus::Winner->value,
        ]);

        $result = LotteryResult::factory()->create([
            'lottery_run_id' => $draw->id,
            'lottery_participant_id' => $participant->id,
            'application_id' => $participant->application_id,
            'user_id' => $participant->user_id,
            'draw_order' => 1,
            'result_type' => LotteryResultType::Selected->value,
            'status' => LotteryResultStatus::Validated->value,
            'selected' => true,
        ]);

        $winner = WinnerRegistration::factory()->create([
            'lottery_run_id' => $draw->id,
            'lottery_draw_result_id' => $result->id,
            'application_id' => $result->application_id,
            'user_id' => $result->user_id,
        ]);

        DrawConvocation::factory()->create([
            'lottery_run_id' => $draw->id,
            'application_id' => $participant->application_id,
            'user_id' => $participant->user_id,
            'lottery_participant_id' => $participant->id,
        ]);

        DrawAttendance::factory()->create([
            'lottery_run_id' => $draw->id,
            'application_id' => $participant->application_id,
            'user_id' => $participant->user_id,
            'lottery_participant_id' => $participant->id,
        ]);

        RankingUpdateRun::factory()->create(['lottery_run_id' => $draw->id, 'contest_id' => $draw->contest_id]);
        PostDrawReport::factory()->create(['lottery_run_id' => $draw->id, 'contest_id' => $draw->contest_id]);
        KeyHandoverAppointment::factory()->create(['winner_registration_id' => $winner->id, 'application_id' => $winner->application_id, 'user_id' => $winner->user_id]);
        TenantTransition::factory()->create(['winner_registration_id' => $winner->id, 'application_id' => $winner->application_id, 'user_id' => $winner->user_id]);
    }
}
