<?php

namespace Database\Factories;

use App\Enums\ComplaintStatus;
use App\Models\Application;
use App\Models\Complaint;
use App\Models\ProvisionalList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Complaint> */
class ComplaintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provisional_list_id' => ProvisionalList::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'complaint_number' => 'REC-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'status' => ComplaintStatus::Draft->value,
            'subject' => 'Reclamação fictícia',
            'grounds' => 'Fundamentos fictícios com dimensão suficiente.',
            'candidate_visible' => true,
        ];
    }
}
