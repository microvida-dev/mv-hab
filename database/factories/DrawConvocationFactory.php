<?php

namespace Database\Factories;

use App\Enums\ConvocationStatus;
use App\Models\Application;
use App\Models\Contest;
use App\Models\DrawConvocation;
use App\Models\LotteryDraw;
use App\Models\LotteryParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DrawConvocation> */
class DrawConvocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lottery_run_id' => LotteryDraw::factory(),
            'contest_id' => Contest::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'lottery_participant_id' => LotteryParticipant::factory(),
            'status' => ConvocationStatus::Generated->value,
            'scheduled_for' => now()->addDays(3),
            'location' => 'Sala municipal de testes',
            'instructions' => 'A convocatória indica a data, hora, local e instruções do ato. A falta de comparência pode produzir efeitos no procedimento, nos termos aplicáveis ao concurso.',
            'generated_at' => now(),
        ];
    }
}
