<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Application;
use App\Models\DrawAttendance;
use App\Models\DrawConvocation;
use App\Models\LotteryDraw;
use App\Models\LotteryParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DrawAttendance> */
class DrawAttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lottery_run_id' => LotteryDraw::factory(),
            'draw_convocation_id' => DrawConvocation::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'lottery_participant_id' => LotteryParticipant::factory(),
            'status' => AttendanceStatus::Pending->value,
        ];
    }
}
