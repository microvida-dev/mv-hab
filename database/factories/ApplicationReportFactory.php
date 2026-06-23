<?php

namespace Database\Factories;

use App\Enums\ApplicationReportStatus;
use App\Enums\ReportFormat;
use App\Models\Application;
use App\Models\ApplicationReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ApplicationReport> */
class ApplicationReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'report_number' => 'REL-CAND-TEST-'.fake()->unique()->numerify('######'),
            'application_id' => Application::factory(),
            'user_id' => fn (array $attributes) => Application::query()->find($attributes['application_id'])->user_id ?? User::factory(),
            'contest_id' => fn (array $attributes) => Application::query()->find($attributes['application_id'])?->contest_id,
            'status' => ApplicationReportStatus::Generated,
            'format' => ReportFormat::Html,
            'title' => 'Relatório operacional de candidatura',
            'summary' => 'Relatório fictício para testes automatizados.',
            'payload' => ['source' => 'factory'],
            'file_path' => null,
            'generated_by' => User::factory(),
            'generated_at' => now(),
        ];
    }
}
