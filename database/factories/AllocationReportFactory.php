<?php

namespace Database\Factories;

use App\Enums\AllocationReportStatus;
use App\Models\AllocationReport;
use App\Models\AllocationRun;
use App\Models\Contest;
use App\Models\DefinitiveList;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AllocationReport> */
class AllocationReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'allocation_run_id' => AllocationRun::factory(),
            'program_id' => Program::factory(),
            'contest_id' => Contest::factory(),
            'definitive_list_id' => DefinitiveList::factory(),
            'report_number' => 'RA-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'title' => 'Ata de atribuição fictícia',
            'status' => AllocationReportStatus::Generated->value,
            'summary' => 'Relatório fictício para testes.',
            'method_description' => 'Ordenação por ranking',
            'results_summary' => ['allocations' => 1],
            'exceptions_summary' => [],
            'refusals_summary' => [],
            'reserve_summary' => [],
            'generated_by' => User::factory(),
            'generated_at' => now(),
        ];
    }
}
