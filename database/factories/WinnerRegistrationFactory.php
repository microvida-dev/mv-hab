<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\LotteryDraw;
use App\Models\LotteryResult;
use App\Models\User;
use App\Models\WinnerRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WinnerRegistration> */
class WinnerRegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lottery_run_id' => LotteryDraw::factory(),
            'lottery_draw_result_id' => LotteryResult::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'status' => 'active',
            'registered_at' => now(),
        ];
    }
}
