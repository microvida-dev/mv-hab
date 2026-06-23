<?php

namespace Database\Factories;

use App\Enums\GeneratedProcedureDocumentStatus;
use App\Enums\ProcedureTemplateType;
use App\Enums\ReportFormat;
use App\Models\GeneratedProcedureDocument;
use App\Models\ProcedureTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GeneratedProcedureDocument> */
class GeneratedProcedureDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_number' => 'DOC-PROC-TEST-'.fake()->unique()->numerify('######'),
            'procedure_template_id' => ProcedureTemplate::factory(),
            'type' => ProcedureTemplateType::ProcedureMinute,
            'status' => GeneratedProcedureDocumentStatus::Generated,
            'title' => 'Documento de procedimento fictício',
            'format' => ReportFormat::Html,
            'payload' => ['source' => 'factory'],
            'content_snapshot' => '<p>Documento fictício.</p>',
            'file_path' => null,
            'generated_by' => User::factory(),
            'generated_at' => now(),
        ];
    }
}
