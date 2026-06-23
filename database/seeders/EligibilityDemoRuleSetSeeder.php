<?php

namespace Database\Seeders;

use App\Enums\EligibilityRuleSetStatus;
use App\Models\Contest;
use App\Models\EligibilityRuleSet;
use App\Models\User;
use Illuminate\Database\Seeder;

class EligibilityDemoRuleSetSeeder extends Seeder
{
    public function run(): void
    {
        $contest = Contest::query()->where('code', 'CAA-DEMO-2026-01')->first();

        if (! $contest) {
            return;
        }

        $administrator = User::query()->where('email', 'admin@example.com')->first();
        $ruleSet = EligibilityRuleSet::query()->updateOrCreate(
            [
                'program_id' => $contest->program_id,
                'contest_id' => $contest->id,
                'name' => 'Condições demo — Concurso 2026',
            ],
            [
                'description' => 'Configuração fictícia de demonstração, sem valor de decisão administrativa.',
                'status' => EligibilityRuleSetStatus::Active->value,
                'is_default' => false,
                'starts_at' => now()->subDay(),
                'ends_at' => $contest->closes_at,
                'created_by' => $administrator?->id,
                'updated_by' => $administrator?->id,
            ],
        );

        $baseSeeder = app(EligibilityBaseCriteriaSeeder::class);
        foreach ($baseSeeder->criteria(true) as $criterion) {
            $ruleSet->criteria()->updateOrCreate(['code' => $criterion['code']], $criterion);
        }
    }
}
