<?php

namespace Database\Factories;

use App\Enums\DocumentDossierStatus;
use App\Models\Application;
use App\Models\DocumentDossier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DocumentDossier> */
class DocumentDossierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'dossier_number' => 'DOS-CAND-TEST-'.fake()->unique()->numerify('######'),
            'application_id' => Application::factory(),
            'user_id' => fn (array $attributes) => Application::query()->find($attributes['application_id'])->user_id ?? User::factory(),
            'contest_id' => fn (array $attributes) => Application::query()->find($attributes['application_id'])?->contest_id,
            'status' => DocumentDossierStatus::Standardized,
            'title' => 'Dossier documental',
            'summary' => 'Dossier fictício para testes.',
            'standardization_payload' => ['documents' => []],
            'missing_documents_count' => 0,
            'rejected_documents_count' => 0,
            'expired_documents_count' => 0,
            'validated_documents_count' => 0,
            'created_by' => User::factory(),
            'standardized_at' => now(),
        ];
    }
}
