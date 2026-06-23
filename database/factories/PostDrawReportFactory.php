<?php

namespace Database\Factories;

use App\Models\Contest;
use App\Models\LotteryDraw;
use App\Models\PostDrawReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PostDrawReport> */
class PostDrawReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lottery_run_id' => LotteryDraw::factory(),
            'contest_id' => Contest::factory(),
            'report_number' => 'RPS-'.fake()->unique()->numerify('######'),
            'status' => 'generated',
            'title' => 'Relatório pós-sorteio fictício',
            'summary' => 'Relatório fictício para testes.',
            'generated_at' => now(),
        ];
    }
}
