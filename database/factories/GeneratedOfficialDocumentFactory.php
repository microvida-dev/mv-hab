<?php

namespace Database\Factories;

use App\Enums\DocumentGenerationStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\GeneratedOfficialDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GeneratedOfficialDocument> */
class GeneratedOfficialDocumentFactory extends Factory
{
    public function definition(): array
    {
        $content = '<html><body>Documento oficial fictício.</body></html>';

        return [
            'document_number' => 'DOC-TEST-'.fake()->unique()->numerify('########'),
            'document_template_id' => DocumentTemplate::factory(),
            'document_template_version_id' => fn (array $attributes) => DocumentTemplateVersion::factory()->create([
                'document_template_id' => $attributes['document_template_id'],
            ])->id,
            'status' => DocumentGenerationStatus::Generated,
            'title' => 'Documento fictício',
            'html_content' => $content,
            'storage_disk' => 'local',
            'storage_path' => 'official-documents/test/fictitious.html',
            'mime_type' => 'text/html',
            'file_size' => strlen($content),
            'checksum' => hash('sha256', $content),
            'generated_at' => now(),
        ];
    }
}
