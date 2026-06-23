<?php

namespace Database\Factories;

use App\Enums\AdhesionRegistrationStatus;
use App\Models\AdhesionRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdhesionRegistration>
 */
class AdhesionRegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => AdhesionRegistrationStatus::Incomplete->value,
            'full_name' => 'Candidato de Teste',
            'email' => fake()->unique()->userName().'@example.test',
            'phone' => null,
            'mobile_phone' => null,
            'document_type' => 'Documento de teste',
            'document_number' => 'DOC-TEST-'.fake()->unique()->numerify('#####'),
            'document_valid_until' => today()->addYears(5),
            'nif' => 'TEST-'.fake()->unique()->numerify('#####'),
            'birth_date' => today()->subYears(30),
            'nationality' => 'Portuguesa',
            'address' => 'Morada de demonstração',
            'postal_code' => '0000-000',
            'city' => 'Localidade de Teste',
            'parish' => 'Freguesia de Teste',
            'municipality' => 'Município de Teste',
            'wants_email_notifications' => true,
            'wants_sms_notifications' => false,
            'wants_postal_notifications' => false,
            'accepts_terms' => true,
            'accepts_data_processing' => true,
            'accepted_terms_at' => now(),
            'accepted_data_processing_at' => now(),
        ];
    }

    public function registered(): static
    {
        return $this->state(fn () => [
            'status' => AdhesionRegistrationStatus::Registered->value,
            'submitted_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => AdhesionRegistrationStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);
    }
}
