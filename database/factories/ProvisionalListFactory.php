<?php

namespace Database\Factories;

use App\Enums\AnonymizationMode;
use App\Enums\ProvisionalListStatus;
use App\Models\ProvisionalList;
use App\Models\RankingSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProvisionalList> */
class ProvisionalListFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ranking_snapshot_id' => RankingSnapshot::factory(),
            'list_number' => 'LP-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'title' => 'Lista provisória fictícia',
            'description' => 'Lista provisória para testes.',
            'status' => ProvisionalListStatus::Draft->value,
            'version_number' => 1,
            'generated_by' => User::factory(),
            'generated_at' => now(),
            'anonymization_mode' => AnonymizationMode::PublicIdentifierOnly->value,
            'public_visibility' => false,
        ];
    }

    public function complaintOpen(): static
    {
        return $this->state(fn () => [
            'status' => ProvisionalListStatus::ComplaintPeriodOpen->value,
            'published_at' => now()->subDay(),
            'complaint_period_starts_at' => now()->subDay(),
            'complaint_period_ends_at' => now()->addWeek(),
        ]);
    }
}
