<?php

namespace Database\Factories;

use App\Enums\ProcessActionStatus;
use App\Enums\ProcessActionType;
use App\Models\Application;
use App\Models\ProcessAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProcessAction> */
class ProcessActionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'action_number' => 'ACT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'application_id' => Application::factory(),
            'user_id' => User::factory(),
            'action_type' => ProcessActionType::ConfirmData->value,
            'status' => ProcessActionStatus::Available->value,
            'title' => 'Confirmar dados',
            'description' => 'Ação processual fictícia para teste.',
        ];
    }
}
