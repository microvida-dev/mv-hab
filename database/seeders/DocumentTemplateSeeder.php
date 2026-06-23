<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use Illuminate\Database\Seeder;

class DocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'application_submission_receipt' => 'Comprovativo de submissão de candidatura',
            'document_rejection_notice' => 'Notificação de rejeição de documento',
            'correction_request_notice' => 'Pedido de aperfeiçoamento',
            'provisional_list_notice' => 'Notificação de publicação de lista provisória',
            'complaint_decision_notice' => 'Decisão de reclamação',
            'hearing_notice' => 'Audiência de interessados',
            'final_list_notice' => 'Notificação de lista definitiva',
            'allocation_notice' => 'Notificação de atribuição de habitação',
            'contract_issue_letter' => 'Carta de emissão de contrato',
            'payment_overdue_notice' => 'Aviso de pagamento em atraso',
            'generic_official_notice_document' => 'Aviso genérico oficial',
        ] as $code => $name) {
            $body = "Exmo.(a) {{ recipient_name }},\n\nEsta minuta refere-se ao evento {{ event_reference }} no âmbito do processo habitacional municipal.\n\nConsulte a sua área pessoal para mais detalhes e cumpra o prazo {{ deadline }}, quando aplicável.\n\nTEMPLATE DEMO — SUJEITO A VALIDAÇÃO MUNICIPAL/JURÍDICA";
            $template = DocumentTemplate::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => 'TEMPLATE DEMO — SUJEITO A VALIDAÇÃO MUNICIPAL/JURÍDICA',
                    'category' => 'official_notice',
                    'status' => 'active',
                    'language' => 'pt-PT',
                    'title' => $name,
                    'body' => $body,
                    'header' => '{{ municipality_name }}',
                    'footer' => 'Documento gerado pela plataforma MV HAB. Minuta sujeita a validação.',
                    'is_official' => true,
                    'is_default' => true,
                    'requires_approval' => true,
                ],
            );
            $version = DocumentTemplateVersion::query()->updateOrCreate(
                ['document_template_id' => $template->id, 'version_number' => 1],
                [
                    'status' => 'active',
                    'title' => $name,
                    'body' => $body,
                    'header' => '{{ municipality_name }}',
                    'footer' => 'Documento gerado pela plataforma MV HAB. Minuta sujeita a validação.',
                    'variables_schema' => ['recipient_name', 'event_reference', 'deadline', 'municipality_name'],
                    'change_summary' => 'Versão demo inicial, sujeita a validação municipal e jurídica.',
                    'approved_at' => now(),
                    'activated_at' => now(),
                ],
            );
            $template->forceFill(['active_version_id' => $version->id])->save();
        }
    }
}
