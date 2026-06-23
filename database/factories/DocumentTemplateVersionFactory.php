<?php

namespace Database\Factories;

use App\Enums\TemplateStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DocumentTemplateVersion> */
class DocumentTemplateVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_template_id' => DocumentTemplate::factory(),
            'version_number' => 1,
            'status' => TemplateStatus::Draft,
            'title' => 'Documento fictício',
            'body' => 'Conteúdo fictício.',
            'variables_schema' => [],
            'change_summary' => 'Versão de teste.',
        ];
    }
}
