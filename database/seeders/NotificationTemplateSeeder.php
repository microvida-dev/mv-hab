<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateVersion;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $definition) {
            $channel = str_ends_with($definition['code'], '_email') ? 'email' : 'in_app';
            $template = NotificationTemplate::query()->updateOrCreate(
                ['code' => $definition['code'], 'channel' => $channel],
                [
                    'name' => $definition['name'],
                    'description' => 'TEMPLATE DEMO — SUJEITO A VALIDAÇÃO MUNICIPAL/JURÍDICA',
                    'template_type' => $channel,
                    'status' => 'active',
                    'language' => 'pt-PT',
                    'subject' => $definition['title'],
                    'title' => $definition['title'],
                    'body' => $definition['body'],
                    'html_body' => '<p>'.nl2br(e($definition['body'])).'</p>',
                    'requires_acknowledgement' => $definition['acknowledgement'],
                    'is_official' => true,
                    'is_default' => true,
                ],
            );

            $version = NotificationTemplateVersion::query()->updateOrCreate(
                ['notification_template_id' => $template->id, 'version_number' => 1],
                [
                    'status' => 'active',
                    'subject' => $definition['title'],
                    'title' => $definition['title'],
                    'body' => $definition['body'],
                    'html_body' => '<p>'.nl2br(e($definition['body'])).'</p>',
                    'variables_schema' => ['recipient_name', 'event_reference', 'deadline', 'action_url'],
                    'change_summary' => 'Versão demo inicial, sujeita a validação municipal e jurídica.',
                    'approved_at' => now(),
                    'activated_at' => now(),
                ],
            );

            $template->forceFill(['active_version_id' => $version->id])->save();
        }
    }

    /**
     * @return list<array{code: string, name: string, title: string, body: string, acknowledgement: bool}>
     */
    private function templates(): array
    {
        $events = [
            'registration_created' => ['Registo de adesão criado', 'O seu registo de adesão foi criado.', false],
            'application_submitted' => ['Candidatura submetida', 'A candidatura com a referência {{ event_reference }} foi submetida.', false],
            'document_rejected' => ['Documento rejeitado', 'O documento associado à referência {{ event_reference }} necessita de correção.', true],
            'correction_requested' => ['Aperfeiçoamento solicitado', 'Foi solicitado o aperfeiçoamento do processo {{ event_reference }} até {{ deadline }}.', true],
            'provisional_list_published' => ['Lista provisória publicada', 'Foi publicada uma lista provisória associada à referência {{ event_reference }}.', false],
            'complaint_decided' => ['Decisão sobre reclamação', 'Foi registada uma decisão relativa à reclamação {{ event_reference }}.', true],
            'housing_allocated' => ['Habitação atribuída', 'Foi registada uma atribuição habitacional com a referência {{ event_reference }}.', true],
            'contract_issued' => ['Contrato emitido', 'Foi emitido um contrato associado à referência {{ event_reference }}.', true],
            'payment_overdue' => ['Pagamento em atraso', 'Existe uma prestação em atraso associada à referência {{ event_reference }}.', true],
        ];

        $templates = [];
        foreach ($events as $event => [$title, $message, $acknowledgement]) {
            foreach (['in_app', 'email'] as $channel) {
                if (in_array($event, ['provisional_list_published'], true) && $channel === 'email') {
                    continue;
                }
                $templates[] = [
                    'code' => $event.'_'.$channel,
                    'name' => $title.' · '.($channel === 'email' ? 'Email' : 'Área pessoal'),
                    'title' => $title,
                    'body' => "Olá {{ recipient_name }},\n\n{$message}\n\nConsulte a sua área pessoal para mais detalhes. Responda dentro do prazo indicado, quando aplicável.\n\nTEMPLATE DEMO — SUJEITO A VALIDAÇÃO MUNICIPAL/JURÍDICA",
                    'acknowledgement' => $acknowledgement,
                ];
            }
        }

        return $templates;
    }
}
