<?php

namespace Database\Factories;

use App\Enums\HearingStatus;
use App\Enums\HearingType;
use App\Models\Application;
use App\Models\Hearing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Hearing> */
class HearingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'hearing_number' => 'AUD-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'status' => HearingStatus::Draft->value,
            'hearing_type' => HearingType::Other->value,
            'subject' => 'Audiência fictícia',
            'message' => 'Mensagem fictícia.',
            'grounds' => 'Fundamentos fictícios.',
            'deadline_at' => now()->addDays(10),
            'candidate_visible' => false,
        ];
    }
}
