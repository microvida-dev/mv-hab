<?php

namespace Database\Factories;

use App\Enums\ComplaintDecisionResult;
use App\Enums\ComplaintDecisionStatus;
use App\Models\Application;
use App\Models\Complaint;
use App\Models\ComplaintDecision;
use App\Models\ProvisionalList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ComplaintDecision> */
class ComplaintDecisionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'complaint_id' => Complaint::factory(),
            'application_id' => Application::factory()->submitted(),
            'provisional_list_id' => ProvisionalList::factory(),
            'decision_number' => 'DEC-REC-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'status' => ComplaintDecisionStatus::Proposed->value,
            'decision_result' => ComplaintDecisionResult::Rejected->value,
            'summary' => 'Decisão fictícia.',
            'grounds' => 'Fundamentação fictícia suficiente.',
            'requires_list_update' => false,
            'proposed_by' => User::factory(),
            'proposed_at' => now(),
            'candidate_visible' => false,
        ];
    }
}
