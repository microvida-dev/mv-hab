<?php

namespace Database\Factories;

use App\Enums\ProcessActionStatus;
use App\Models\AdditionalDocumentRequest;
use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AdditionalDocumentRequest> */
class AdditionalDocumentRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_number' => 'DOC-ADD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'application_id' => Application::factory(),
            'user_id' => User::factory(),
            'status' => ProcessActionStatus::Available->value,
            'title' => 'Documento adicional fictício',
            'description' => fake()->sentence(),
            'due_at' => now()->addDays(10),
            'issued_at' => now(),
        ];
    }
}
