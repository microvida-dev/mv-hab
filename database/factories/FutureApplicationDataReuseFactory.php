<?php

namespace Database\Factories;

use App\Enums\DataReuseStatus;
use App\Models\FutureApplicationDataReuse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FutureApplicationDataReuse> */
class FutureApplicationDataReuseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => DataReuseStatus::RequiresConfirmation->value,
            'sections' => ['dados_pessoais', 'agregado'],
            'source_snapshot' => ['documents_summary' => ['validity_not_copied' => true]],
            'warnings' => ['Os documentos não são copiados automaticamente como válidos.'],
            'expires_at' => now()->addDays(30),
        ];
    }
}
