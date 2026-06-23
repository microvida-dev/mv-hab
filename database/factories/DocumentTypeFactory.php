<?php

namespace Database\Factories;

use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentCategory;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentType>
 */
class DocumentTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Documento de teste '.fake()->unique()->word();

        return [
            'code' => Str::slug($name).'_'.fake()->unique()->numerify('###'),
            'name' => $name,
            'description' => 'Tipo documental fictício para testes.',
            'category' => DocumentCategory::Other->value,
            'applies_to' => DocumentAppliesTo::AdhesionRegistration->value,
            'is_active' => true,
            'is_required_by_default' => false,
            'requires_expiry_date' => false,
            'requires_issue_date' => false,
            'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
            'max_file_size_mb' => 10,
            'sort_order' => 0,
        ];
    }
}
