<?php

namespace Database\Factories;

use App\Enums\TemplateStatus;
use App\Models\DocumentTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DocumentTemplate> */
class DocumentTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'document.'.fake()->unique()->slug(2),
            'name' => 'Modelo documental fictício',
            'description' => 'MINUTA DEMO sujeita a validação.',
            'category' => 'general',
            'status' => TemplateStatus::Draft,
            'language' => 'pt-PT',
            'title' => 'Documento fictício',
            'body' => 'Documento de demonstração sem valor jurídico.',
            'is_official' => false,
            'is_default' => false,
            'requires_approval' => true,
        ];
    }
}
