<?php

namespace Database\Seeders;

use App\Models\TemplateVariable;
use Illuminate\Database\Seeder;

class TemplateVariableSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'recipient_name', 'name' => 'Nome do destinatário', 'variable_type' => 'string', 'source_key' => 'recipient.name', 'example_value' => 'Cidadão de Demonstração'],
            ['code' => 'event_reference', 'name' => 'Referência do evento', 'variable_type' => 'string', 'source_key' => 'related.reference', 'example_value' => 'REF-DEMO-2026-001'],
            ['code' => 'deadline', 'name' => 'Prazo aplicável', 'variable_type' => 'date', 'source_key' => 'related.deadline', 'example_value' => '30/06/2026'],
            ['code' => 'action_url', 'name' => 'Ligação para a área pessoal', 'variable_type' => 'url', 'source_key' => 'action_url', 'example_value' => 'https://example.test/area-candidato'],
            ['code' => 'municipality_name', 'name' => 'Município', 'variable_type' => 'string', 'source_key' => 'municipality.name', 'example_value' => 'Município de Demonstração'],
            ['code' => 'application_number', 'name' => 'Número da candidatura', 'variable_type' => 'string', 'source_key' => 'application.application_number', 'example_value' => 'CAN-DEMO-2026-001'],
            ['code' => 'document_type', 'name' => 'Tipo de documento', 'variable_type' => 'string', 'source_key' => 'document.type', 'example_value' => 'Documento de demonstração'],
            ['code' => 'process_number', 'name' => 'Número do processo', 'variable_type' => 'string', 'source_key' => 'process.process_number', 'example_value' => 'PROC-DEMO-2026-001'],
            ['code' => 'amount', 'name' => 'Montante', 'variable_type' => 'currency', 'source_key' => 'financial.amount', 'example_value' => '125,00 EUR'],
            ['code' => 'housing_reference', 'name' => 'Referência da habitação', 'variable_type' => 'string', 'source_key' => 'allocation.housing_reference', 'example_value' => 'HAB-DEMO-001'],
        ] as $variable) {
            TemplateVariable::query()->updateOrCreate(
                ['code' => $variable['code']],
                $variable + [
                    'description' => 'Variável de demonstração para templates parametrizáveis.',
                    'is_required' => false,
                    'is_sensitive' => false,
                    'is_active' => true,
                ],
            );
        }
    }
}
