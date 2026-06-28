<?php

namespace Tests\Feature\UX;

use App\Models\Citizen;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniversalSearchRgpdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_search_results_do_not_expose_sensitive_citizen_fields(): void
    {
        $administrator = $this->userWithRole('administrator');
        Citizen::factory()->create([
            'name' => 'Maria Pesquisa Segura',
            'document_number' => 'DOC-MASCARADO-UX05',
            'email' => 'maria.pesquisa@example.test',
            'phone' => '000000000',
            'address' => 'Rua Privada de Teste',
        ]);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.search.index', ['q' => 'Maria Pesquisa']))
            ->assertOk()
            ->assertSee('Maria Pesquisa Segura')
            ->assertDontSee('DOC-MASCARADO-UX05')
            ->assertDontSee('maria.pesquisa@example.test')
            ->assertDontSee('000000000')
            ->assertDontSee('Rua Privada de Teste')
            ->assertDontSee('storage/app/private');
    }

    public function test_support_ticket_result_does_not_render_subject_or_description_with_pii(): void
    {
        $administrator = $this->userWithRole('administrator');
        SupportTicket::factory()->create([
            'ticket_number' => 'SUP-UX05-PII',
            'subject' => 'Assunto com NIF mascarado',
            'description' => 'Descrição com morada privada e storage_path interno.',
        ]);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.search.index', ['q' => 'SUP-UX05-PII']))
            ->assertOk()
            ->assertSee('Ticket SUP-UX05-PII')
            ->assertDontSee('Assunto com NIF')
            ->assertDontSee('NIF mascarado')
            ->assertDontSee('morada privada')
            ->assertDontSee('storage_path');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
