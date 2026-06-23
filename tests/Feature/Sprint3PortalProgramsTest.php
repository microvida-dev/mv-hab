<?php

namespace Tests\Feature;

use App\Enums\ContestDeadlineType;
use App\Enums\ContestStatus;
use App\Enums\ProgramStatus;
use App\Models\Contest;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint3PortalProgramsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_only_expose_published_programs_and_contests(): void
    {
        $publishedProgram = Program::factory()->published()->create([
            'name' => 'Programa Público Municipal',
        ]);
        $draftProgram = Program::factory()->create([
            'name' => 'Programa Interno em Preparação',
        ]);
        $archivedProgram = Program::factory()->create([
            'name' => 'Programa Público Arquivado',
            'status' => ProgramStatus::Archived->value,
            'published_at' => now()->subMonth(),
        ]);

        $publishedContest = Contest::factory()->open()->for($publishedProgram)->create([
            'title' => 'Concurso Público Aberto',
        ]);
        $draftContest = Contest::factory()->for($publishedProgram)->create([
            'title' => 'Concurso Ainda em Rascunho',
        ]);
        $archivedContest = Contest::factory()->for($publishedProgram)->create([
            'title' => 'Concurso Público Arquivado',
            'status' => ContestStatus::Archived->value,
            'published_at' => now()->subMonth(),
        ]);

        $this->get(route('public.programs.index'))
            ->assertOk()
            ->assertSee($publishedProgram->name)
            ->assertDontSee($draftProgram->name)
            ->assertDontSee($archivedProgram->name);

        $this->get(route('public.contests.index'))
            ->assertOk()
            ->assertSee($publishedContest->title)
            ->assertDontSee($draftContest->title)
            ->assertDontSee($archivedContest->title);

        $this->get(route('public.programs.show', $draftProgram->slug))->assertNotFound();
        $this->get(route('public.programs.show', $archivedProgram->slug))->assertNotFound();
        $this->get(route('public.contests.show', $draftContest->slug))->assertNotFound();
        $this->get(route('public.contests.show', $archivedContest->slug))->assertNotFound();
    }

    public function test_open_contest_detail_displays_account_and_login_calls_to_action(): void
    {
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->open()->for($program)->create();

        $this->get(route('public.contests.show', $contest->slug))
            ->assertOk()
            ->assertSee('Candidaturas abertas')
            ->assertSee('Criar conta para candidatar-me')
            ->assertSee('Já tenho conta')
            ->assertSee('A candidatura exige Registo de Adesão finalizado');
    }

    public function test_public_portal_distinguishes_internal_and_candidate_sessions(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->open()->for($program)->create();

        $administrator = User::factory()->create();
        $administrator->assignRole('administrator');

        $this->actingAs($administrator)
            ->get(route('public.contests.show', $contest->slug))
            ->assertOk()
            ->assertSee('Backoffice')
            ->assertSee('Está autenticado com um perfil interno')
            ->assertSee('Terminar sessão')
            ->assertDontSee('Iniciar candidatura');

        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $this->actingAs($candidate)
            ->get(route('public.contests.show', $contest->slug))
            ->assertOk()
            ->assertSee('Área do candidato')
            ->assertSee('Iniciar candidatura')
            ->assertSee('Sair')
            ->assertDontSee('Está autenticado com um perfil interno');
    }

    public function test_homepage_and_faq_provide_public_process_guidance(): void
    {
        $this->get(route('public.portal'))
            ->assertOk()
            ->assertSee('Como funciona')
            ->assertSee('Antes de se candidatar')
            ->assertSee('Precisa de ajuda?')
            ->assertDontSee('/admin/programs')
            ->assertDontSee('Auditoria');

        $this->get(route('public.faq'))
            ->assertOk()
            ->assertSee('O que é o Arrendamento Acessível?')
            ->assertSee('Quem pode candidatar-se?')
            ->assertSee('A candidatura online já está disponível?')
            ->assertSee('Onde posso pedir apoio?');
    }

    public function test_backoffice_requires_authentication_and_program_permission(): void
    {
        $this->get(route('admin.programs.index'))
            ->assertRedirect(route('login'));

        $this->seed(SystemAccessSeeder::class);
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $this->actingAs($candidate)
            ->get(route('admin.programs.index'))
            ->assertForbidden();
    }

    public function test_administrator_can_create_program_without_mass_assigning_status(): void
    {
        $administrator = $this->administrator();
        $municipality = Municipality::factory()->create();

        $response = $this->actingAs($administrator)->post(route('admin.programs.store'), [
            'municipality_id' => $municipality->id,
            'name' => 'Programa de Arrendamento Municipal',
            'slug' => '',
            'summary' => 'Programa público municipal destinado a apoiar o acesso a habitação.',
            'description' => 'Descrição institucional do programa para consulta pública.',
            'legal_basis' => 'Regulamento municipal aplicável.',
            'starts_at' => today()->toDateString(),
            'ends_at' => today()->addYear()->toDateString(),
            'status' => ProgramStatus::Published->value,
            'rules' => [
                [
                    'title' => 'Âmbito',
                    'description' => 'Condições gerais de participação no programa.',
                    'effective_from' => today()->toDateString(),
                    'effective_until' => today()->addYear()->toDateString(),
                ],
            ],
        ]);

        $program = Program::query()->where('name', 'Programa de Arrendamento Municipal')->firstOrFail();

        $response->assertRedirect(route('admin.programs.show', $program));
        $this->assertSame(ProgramStatus::Draft, $program->status);
        $this->assertSame(1, $program->rules()->count());
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $administrator->id,
            'auditable_type' => $program->getMorphClass(),
            'auditable_id' => $program->id,
            'module' => 'programs',
            'action' => 'create',
        ]);
    }

    public function test_program_requires_a_rule_before_publication(): void
    {
        $administrator = $this->administrator();
        $program = Program::factory()->create();

        $this->actingAs($administrator)
            ->post(route('admin.programs.publish', $program))
            ->assertSessionHasErrors('program');

        $this->assertSame(ProgramStatus::Draft, $program->fresh()->status);
    }

    public function test_administrator_can_create_and_publish_contest_with_formal_deadline(): void
    {
        $administrator = $this->administrator();
        $program = Program::factory()->published()->create();

        $response = $this->actingAs($administrator)->post(route('admin.contests.store'), [
            'program_id' => $program->id,
            'code' => 'CAA-TEST-2026',
            'slug' => '',
            'title' => 'Concurso Municipal de Teste',
            'summary' => 'Concurso institucional de teste sem dados pessoais.',
            'description' => 'Descrição pública do concurso municipal.',
            'application_instructions' => 'Consulte os prazos antes de preparar a candidatura.',
            'opens_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'closes_at' => now()->addMonth()->format('Y-m-d H:i:s'),
            'status' => ContestStatus::Published->value,
            'deadlines' => [
                [
                    'type' => ContestDeadlineType::Applications->value,
                    'label' => 'Prazo de candidatura',
                    'starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
                    'ends_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                    'description' => 'Período oficial para apresentação de candidatura.',
                ],
            ],
            'jury_members' => [],
        ]);

        $contest = Contest::query()->where('code', 'CAA-TEST-2026')->firstOrFail();

        $response->assertRedirect(route('admin.contests.show', $contest));
        $this->assertSame(ContestStatus::Draft, $contest->status);
        $this->assertSame(1, $contest->deadlines()->count());

        $this->actingAs($administrator)
            ->post(route('admin.contests.publish', $contest))
            ->assertRedirect();

        $this->assertSame(ContestStatus::Published, $contest->fresh()->status);
        $this->get(route('public.contests.show', $contest->slug))
            ->assertOk()
            ->assertSee($contest->title);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $administrator->id,
            'auditable_type' => $contest->getMorphClass(),
            'auditable_id' => $contest->id,
            'module' => 'contests',
            'action' => 'publish',
        ]);
    }

    public function test_contest_closing_date_must_be_after_opening_date(): void
    {
        $administrator = $this->administrator();
        $program = Program::factory()->published()->create();

        $this->actingAs($administrator)
            ->post(route('admin.contests.store'), [
                'program_id' => $program->id,
                'code' => 'CAA-INVALID-DATES',
                'title' => 'Concurso com Datas Inválidas',
                'summary' => 'Resumo institucional para validação de datas.',
                'description' => 'Descrição institucional para validação de datas.',
                'opens_at' => now()->addWeek()->format('Y-m-d H:i:s'),
                'closes_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasErrors('closes_at');

        $this->assertDatabaseMissing('contests', ['code' => 'CAA-INVALID-DATES']);
    }

    private function administrator(): User
    {
        $this->seed(SystemAccessSeeder::class);

        $administrator = User::factory()->create();
        $administrator->assignRole('administrator');

        return $administrator;
    }
}
