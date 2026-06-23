<?php

namespace Tests\Feature;

use App\Enums\CommunicationAttemptStatus;
use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDeliveryStatus;
use App\Enums\CommunicationReceiptType;
use App\Enums\DocumentGenerationStatus;
use App\Enums\OfficialNotificationType;
use App\Enums\TemplateStatus;
use App\Models\CommunicationReceipt;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\NotificationEventRule;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateVersion;
use App\Models\TemplateVariable;
use App\Models\User;
use App\Services\Documents\OfficialDocumentGenerationService;
use App\Services\Notifications\CommunicationDeliveryService;
use App\Services\Notifications\CommunicationLogService;
use App\Services\Notifications\NotificationEventDispatcher;
use App\Services\Notifications\OfficialNotificationService;
use App\Services\Notifications\TemplateRenderingService;
use Database\Seeders\DocumentTemplateSeeder;
use Database\Seeders\NotificationEventRuleSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\SystemAccessSeeder;
use Database\Seeders\TemplateVariableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Sprint16CommunicationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
        Storage::fake('local');
    }

    public function test_candidate_notification_access_is_isolated_and_backoffice_is_protected(): void
    {
        $candidate = $this->userWithRole('candidate');
        $otherCandidate = $this->userWithRole('candidate');
        $admin = $this->userWithRole('administrator');
        $notification = app(OfficialNotificationService::class)->createInternal(
            user: $candidate,
            type: OfficialNotificationType::Other,
            subject: 'Notificação de teste',
            body: 'Conteúdo exclusivamente fictício.',
            actor: $admin,
        );

        $this->get(route('candidate.notifications.index'))->assertRedirect(route('login'));

        $this->actingAs($candidate)
            ->get(route('candidate.notifications.index'))
            ->assertOk()
            ->assertSee('Notificação de teste');

        $this->actingAs($candidate)
            ->get(route('candidate.notifications.show', $notification))
            ->assertOk();

        $this->actingAs($otherCandidate)
            ->get(route('candidate.notifications.show', $notification))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->get(route('backoffice.communications.index'))
            ->assertForbidden();
    }

    public function test_candidate_can_read_and_acknowledge_own_notification_with_private_receipts(): void
    {
        $candidate = $this->userWithRole('candidate');
        $admin = $this->userWithRole('administrator');
        $notification = app(OfficialNotificationService::class)->createInternal(
            user: $candidate,
            type: OfficialNotificationType::Other,
            subject: 'Decisão de teste',
            body: 'A tomada de conhecimento é obrigatória neste cenário fictício.',
            actor: $admin,
            requiresAcknowledgement: true,
        );

        $this->actingAs($candidate)
            ->post(route('candidate.notifications.mark-read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->refresh()->read_at);
        $this->assertDatabaseHas('communication_receipts', [
            'communication_log_id' => $notification->communication_log_id,
            'receipt_type' => CommunicationReceiptType::ReadProof->value,
        ]);

        $this->actingAs($candidate)
            ->post(route('candidate.notifications.acknowledge', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->refresh()->acknowledged_at);
        $receipt = CommunicationReceipt::query()
            ->where('communication_log_id', $notification->communication_log_id)
            ->where('receipt_type', CommunicationReceiptType::AcknowledgementProof)
            ->firstOrFail();
        Storage::disk('local')->assertExists($receipt->storage_path);
    }

    public function test_admin_manages_versioned_templates_and_auditor_is_read_only(): void
    {
        $admin = $this->userWithRole('administrator');
        $auditor = $this->userWithRole('auditor');
        $recipient = $this->userWithRole('candidate');

        $this->actingAs($admin)
            ->post(route('backoffice.communications.templates.store'), [
                'code' => 'test_notice',
                'name' => 'Template de teste',
                'template_type' => 'in_app',
                'channel' => 'in_app',
                'language' => 'pt-PT',
                'subject' => 'Assunto de teste',
                'title' => 'Título de teste',
                'body' => 'Conteúdo sem variáveis.',
                'is_official' => true,
            ])
            ->assertRedirect();

        $template = NotificationTemplate::query()->firstOrFail();
        $version = $template->versions()->firstOrFail();
        $this->assertSame(TemplateStatus::Draft, $template->status);
        $this->assertSame(1, $version->version_number);

        $this->actingAs($admin)
            ->post(route('backoffice.communications.template-versions.approve', $version))
            ->assertRedirect();
        $this->actingAs($admin)
            ->post(route('backoffice.communications.template-versions.activate', $version))
            ->assertRedirect();

        $this->assertSame(TemplateStatus::Active, $template->refresh()->status);
        $this->assertSame($version->id, $template->active_version_id);

        $this->actingAs($admin)
            ->get(route('backoffice.communications.templates.preview', $template))
            ->assertOk()
            ->assertSee('Conteúdo sem variáveis.');

        $this->actingAs($auditor)
            ->get(route('backoffice.communications.logs.index'))
            ->assertOk();

        $this->actingAs($auditor)
            ->post(route('backoffice.communications.logs.store'), [
                'recipient_user_id' => $recipient->id,
                'event_code' => 'audit.forbidden',
                'channel' => 'in_app',
                'title' => 'Não permitido',
                'body' => 'Não deve ser criado.',
                'priority' => 'normal',
            ])
            ->assertForbidden();
    }

    public function test_event_dispatch_creates_snapshots_delivery_attempt_and_receipt(): void
    {
        $candidate = $this->userWithRole('candidate');
        $admin = $this->userWithRole('administrator');
        $this->createVariable('recipient_name');
        $this->createVariable('event_reference');
        [$template] = $this->activeNotificationTemplate(
            body: 'Olá {{ recipient_name }}, referência {{ event_reference }}.',
        );
        $rule = NotificationEventRule::factory()->create([
            'event_code' => 'application_submitted',
            'recipient_type' => 'custom_user',
            'channel' => CommunicationChannel::InApp,
            'notification_template_id' => $template->id,
            'is_active' => false,
        ]);

        $dispatcher = app(NotificationEventDispatcher::class);
        $context = [
            'recipient_user' => $candidate,
            'variables' => ['recipient_name' => 'Candidato Fictício', 'event_reference' => 'CAN-TEST-001'],
        ];

        $this->assertCount(0, $dispatcher->dispatch('application_submitted', $candidate, $context, $admin));
        $rule->update(['is_active' => true]);
        $created = $dispatcher->dispatch('application_submitted', $candidate, $context, $admin);

        $this->assertCount(1, $created);
        $communication = $created->first();
        $this->assertStringContainsString('CAN-TEST-001', $communication->body_snapshot);
        $this->assertSame(1, $communication->deliveries()->count());
        $delivery = $communication->deliveries()->firstOrFail();
        $this->assertSame(CommunicationDeliveryStatus::Delivered, $delivery->status);
        $this->assertSame(CommunicationAttemptStatus::Success, $delivery->attempts()->firstOrFail()->status);
        $this->assertTrue($communication->receipts()->where('receipt_type', CommunicationReceiptType::SendProof)->exists());
        $this->assertSame(1, $candidate->officialNotifications()->count());
    }

    public function test_template_renderer_rejects_missing_unknown_and_sensitive_sms_variables(): void
    {
        $renderer = app(TemplateRenderingService::class);
        $this->createVariable('known_value');
        TemplateVariable::factory()->create([
            'code' => 'sensitive_value',
            'is_sensitive' => true,
            'is_active' => true,
        ]);

        try {
            $renderer->render(['body' => '{{ known_value }}'], []);
            $this->fail('A variável obrigatória em falta deveria falhar.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('variables', $exception->errors());
        }

        try {
            $renderer->render(['body' => '{{ unknown_value }}'], ['unknown_value' => 'teste']);
            $this->fail('A variável desconhecida deveria falhar.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('desconhecidas', $exception->getMessage());
        }

        $this->expectException(ValidationException::class);
        $renderer->render(
            ['body' => '{{ sensitive_value }}'],
            ['sensitive_value' => 'valor fictício'],
            CommunicationChannel::Sms,
        );
    }

    public function test_unconfigured_email_and_sms_do_not_claim_real_delivery(): void
    {
        config(['mail.default' => 'log', 'mail.from.address' => 'hello@example.com']);
        $candidate = $this->userWithRole('candidate');
        $admin = $this->userWithRole('administrator');
        $communication = app(CommunicationLogService::class)->create(
            eventCode: 'channel.test',
            recipient: $candidate,
            content: ['title' => 'Teste de canais', 'body' => 'Conteúdo fictício.'],
            actor: $admin,
        );
        $deliveries = app(CommunicationDeliveryService::class);

        $email = $deliveries->create($communication, CommunicationChannel::Email, $candidate->email);
        $deliveries->execute($email, $admin);
        $this->assertSame(CommunicationDeliveryStatus::PendingConfiguration, $email->refresh()->status);
        $this->assertNull($email->sent_at);

        $sms = $deliveries->create($communication, CommunicationChannel::Sms, '+351000000000');
        $deliveries->execute($sms, $admin);
        $this->assertSame(CommunicationDeliveryStatus::Disabled, $sms->refresh()->status);
        $this->assertNull($sms->sent_at);
        $this->assertSame(CommunicationAttemptStatus::Skipped, $sms->attempts()->firstOrFail()->status);
    }

    public function test_official_document_is_private_versioned_and_only_visible_to_recipient(): void
    {
        $candidate = $this->userWithRole('candidate');
        $otherCandidate = $this->userWithRole('candidate');
        $admin = $this->userWithRole('administrator');
        foreach (['recipient_name', 'event_reference', 'deadline', 'municipality_name'] as $code) {
            $this->createVariable($code);
        }
        [$template] = $this->activeDocumentTemplate();
        $document = app(OfficialDocumentGenerationService::class)->generate(
            template: $template,
            variables: [
                'recipient_name' => 'Candidato Fictício',
                'event_reference' => 'PROC-TEST-001',
                'deadline' => '30/06/2026',
                'municipality_name' => 'Município de Teste',
            ],
            actor: $admin,
            recipient: $candidate,
        );

        $this->assertSame(DocumentGenerationStatus::Generated, $document->status);
        $this->assertStringContainsString('PROC-TEST-001', $document->html_content);
        Storage::disk('local')->assertExists($document->storage_path);

        $this->actingAs($candidate)
            ->get(route('candidate.official-documents.show', $document))
            ->assertOk()
            ->assertSee($document->document_number);
        $this->actingAs($candidate)
            ->get(route('candidate.official-documents.download', $document))
            ->assertOk();
        $this->actingAs($otherCandidate)
            ->get(route('candidate.official-documents.show', $document))
            ->assertForbidden();
    }

    public function test_candidate_preferences_keep_in_app_channel_mandatory(): void
    {
        $candidate = $this->userWithRole('candidate');

        $this->actingAs($candidate)
            ->put(route('candidate.notification-preferences.update'), [
                'allow_in_app' => false,
                'allow_email' => false,
                'allow_sms' => true,
                'allow_postal' => false,
                'preferred_channel' => 'sms',
                'phone_for_notifications' => '+351000000000',
            ])
            ->assertRedirect();

        $preference = $candidate->notificationPreference()->firstOrFail();
        $this->assertTrue($preference->allow_in_app);
        $this->assertFalse($preference->allow_email);
        $this->assertTrue($preference->allow_sms);
    }

    public function test_demo_catalog_seeders_create_required_validated_templates(): void
    {
        $this->seed(TemplateVariableSeeder::class);
        $this->seed(NotificationTemplateSeeder::class);
        $this->seed(NotificationEventRuleSeeder::class);
        $this->seed(DocumentTemplateSeeder::class);

        $this->assertDatabaseHas('notification_templates', [
            'code' => 'application_submitted_in_app',
            'status' => TemplateStatus::Active->value,
        ]);
        $this->assertDatabaseHas('notification_templates', [
            'code' => 'payment_overdue_email',
            'channel' => CommunicationChannel::Email->value,
        ]);
        $this->assertDatabaseHas('notification_event_rules', [
            'event_code' => 'application_submitted',
            'channel' => CommunicationChannel::InApp->value,
        ]);
        $this->assertDatabaseHas('document_templates', [
            'code' => 'generic_official_notice_document',
            'status' => TemplateStatus::Active->value,
        ]);
        $this->assertStringContainsString(
            'SUJEITO A VALIDAÇÃO MUNICIPAL/JURÍDICA',
            NotificationTemplate::query()->where('code', 'application_submitted_in_app')->firstOrFail()->description,
        );
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function createVariable(string $code): TemplateVariable
    {
        return TemplateVariable::factory()->create([
            'code' => $code,
            'is_active' => true,
            'is_sensitive' => false,
        ]);
    }

    private function activeNotificationTemplate(string $body): array
    {
        $template = NotificationTemplate::factory()->create([
            'status' => TemplateStatus::Active,
            'body' => $body,
        ]);
        $version = NotificationTemplateVersion::factory()->create([
            'notification_template_id' => $template->id,
            'status' => TemplateStatus::Active,
            'body' => $body,
            'activated_at' => now(),
        ]);
        $template->forceFill(['active_version_id' => $version->id])->save();

        return [$template, $version];
    }

    private function activeDocumentTemplate(): array
    {
        $body = 'Exmo.(a) {{ recipient_name }}, referência {{ event_reference }}, prazo {{ deadline }}.';
        $template = DocumentTemplate::factory()->create([
            'status' => TemplateStatus::Active,
            'title' => 'Documento de teste',
            'body' => $body,
            'header' => '{{ municipality_name }}',
        ]);
        $version = DocumentTemplateVersion::factory()->create([
            'document_template_id' => $template->id,
            'status' => TemplateStatus::Active,
            'title' => 'Documento de teste',
            'body' => $body,
            'header' => '{{ municipality_name }}',
            'activated_at' => now(),
        ]);
        $template->forceFill(['active_version_id' => $version->id])->save();

        return [$template, $version];
    }
}
