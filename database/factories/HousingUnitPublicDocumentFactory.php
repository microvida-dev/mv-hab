<?php

namespace Database\Factories;

use App\Enums\HousingUnitPublicDocumentType;
use App\Models\HousingUnit;
use App\Models\HousingUnitPublicDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HousingUnitPublicDocument> */
class HousingUnitPublicDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'housing_unit_id' => HousingUnit::factory(),
            'contest_id' => null,
            'uploaded_by' => null,
            'approved_by' => null,
            'title' => 'Ficha pública da habitação',
            'description' => 'Documento público de demonstração.',
            'document_type' => HousingUnitPublicDocumentType::TechnicalSheet->value,
            'disk' => 'public',
            'path' => 'public-housing/documents/demo.pdf',
            'original_filename' => 'ficha-publica.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2048,
            'checksum' => null,
            'is_public' => true,
            'approved_at' => now()->subHour(),
            'published_at' => now()->subHour(),
            'expires_at' => null,
            'sort_order' => 0,
            'download_count' => 0,
        ];
    }

    public function private(): static
    {
        return $this->state(fn () => [
            'is_public' => false,
            'published_at' => null,
        ]);
    }
}
