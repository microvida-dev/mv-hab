<?php

namespace Database\Factories;

use App\Enums\EligibilityCheckStatus;
use App\Enums\EligibilityCheckType;
use App\Enums\EligibilityResult;
use App\Models\AdhesionRegistration;
use App\Models\EligibilityCheck;
use App\Models\EligibilityRuleSet;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EligibilityCheck>
 */
class EligibilityCheckFactory extends Factory
{
    public function definition(): array
    {
        return [
            'eligibility_rule_set_id' => EligibilityRuleSet::factory(),
            'program_id' => Program::factory(),
            'contest_id' => null,
            'application_id' => null,
            'adhesion_registration_id' => AdhesionRegistration::factory(),
            'user_id' => User::factory(),
            'check_type' => EligibilityCheckType::CandidatePreCheck->value,
            'status' => EligibilityCheckStatus::Completed->value,
            'result' => EligibilityResult::Eligible->value,
            'summary' => 'Resultado fictício para teste.',
            'missing_data' => [],
            'warnings' => [],
            'executed_by' => null,
            'executed_at' => now(),
        ];
    }
}
