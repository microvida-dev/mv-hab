<?php

namespace Tests\Feature\UX;

use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\ProcessTimelineEvent;
use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class CaseWorkspaceRgpdTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_document_workspace_does_not_expose_private_path_or_nif(): void
    {
        $document = DocumentSubmission::factory()->create([
            'title' => 'Comprovativo fictício',
            'storage_path' => 'storage/app/private/123456789.pdf',
        ]);

        $this->actingAs($this->userWithRole())
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.documents.show', $document))
            ->assertOk()
            ->assertSee('Documento privado')
            ->assertDontSee('storage/app/private')
            ->assertDontSee('123456789');
    }

    public function test_application_case_workspace_does_not_expose_sensitive_identifiers_or_private_paths(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $application = Application::factory()->submitted()->create();

        ProcessTimelineEvent::factory()->create([
            'application_id' => $application->id,
            'title' => 'Evento minimizado',
            'description' => 'Contacto fictício com identificador 123456789 e storage/app/private/ficheiro.pdf',
        ]);

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('Evento minimizado')
            ->assertDontSee('123456789')
            ->assertDontSee('storage/app/private')
            ->assertDontSee('email')
            ->assertDontSee('telefone');
    }

    public function test_support_ticket_workspace_does_not_render_free_text_with_sensitive_data(): void
    {
        $ticket = SupportTicket::factory()->create([
            'subject' => 'Assunto com NIF 123456789',
            'description' => 'Morada e rendimento fictícios não devem aparecer no workspace.',
        ]);

        $this->actingAs($this->userWithRole())
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Pedido de apoio')
            ->assertDontSee('123456789')
            ->assertDontSee('Morada e rendimento');
    }
}
