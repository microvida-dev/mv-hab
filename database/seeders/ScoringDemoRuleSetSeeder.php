<?php

namespace Database\Seeders;

use App\Enums\ScoringRuleSetStatus;
use App\Models\Contest;
use App\Models\ScoringRuleSet;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScoringDemoRuleSetSeeder extends Seeder
{
    public function run(): void
    {
        $contest = Contest::query()->where('code', 'CAA-DEMO-2026-01')->first();

        if (! $contest) {
            return;
        }

        $administrator = User::query()->where('email', 'admin@example.com')->first();
        $ruleSet = ScoringRuleSet::query()->updateOrCreate(
            [
                'program_id' => $contest->program_id,
                'contest_id' => $contest->id,
                'name' => 'Classificação demo - Concurso 2026',
            ],
            [
                'description' => 'Configuração fictícia para validação funcional do ranking interno.',
                'status' => ScoringRuleSetStatus::Active->value,
                'is_default' => false,
                'starts_at' => now()->subDay(),
                'ends_at' => $contest->closes_at,
                'created_by' => $administrator?->id,
                'updated_by' => $administrator?->id,
            ],
        );

        $baseSeeder = app(ScoringBaseCriteriaSeeder::class);

        foreach ($baseSeeder->criteria() as $criterion) {
            $ruleSet->criteria()->updateOrCreate(['code' => $criterion['code']], $criterion);
        }

        $baseSeeder->tieBreakers($ruleSet);
    }
}
