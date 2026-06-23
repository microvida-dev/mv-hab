<?php

namespace Database\Factories;

use App\Enums\KeyHandoverStatus;
use App\Models\Application;
use App\Models\KeyHandoverAppointment;
use App\Models\User;
use App\Models\WinnerRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KeyHandoverAppointment> */
class KeyHandoverAppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'winner_registration_id' => WinnerRegistration::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'status' => KeyHandoverStatus::Scheduled->value,
            'scheduled_for' => now()->addWeek(),
            'location' => 'Gabinete municipal de testes',
            'instructions' => 'A entrega de chaves só deve ocorrer após validação dos requisitos administrativos, contratuais e documentais aplicáveis.',
        ];
    }
}
