<?php

namespace Database\Factories;

use App\Enums\ProcedureMinuteStatus;
use App\Models\Contest;
use App\Models\ProcedureMinute;
use App\Models\ProcedureTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProcedureMinute> */
class ProcedureMinuteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'minute_number' => 'ATA-TEST-'.fake()->unique()->numerify('######'),
            'contest_id' => Contest::factory(),
            'procedure_template_id' => ProcedureTemplate::factory(),
            'status' => ProcedureMinuteStatus::Generated,
            'title' => 'Ata fictícia do procedimento',
            'meeting_date' => now()->toDateString(),
            'subject' => 'Acompanhamento do procedimento',
            'summary' => 'Ata fictícia para testes.',
            'content_snapshot' => '<p>Ata fictícia.</p>',
            'payload' => ['source' => 'factory'],
            'generated_by' => User::factory(),
            'generated_at' => now(),
        ];
    }
}
