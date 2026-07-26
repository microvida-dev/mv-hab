<?php

namespace Tests\Feature\Backoffice;

use App\Enums\ProcedureMinuteStatus;
use App\Enums\ProcedureTemplateStatus;
use App\Enums\ProcedureTemplateType;
use App\Models\Application;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use App\Models\ProcedureMinute;
use App\Models\ProcedureTemplate;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\AlcanenaProcedureTemplateSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcedureMinuteManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_guest_is_redirected_from_procedure_minutes_index(): void
    {
        $this->get(route('backoffice.procedure-minutes.index'))
            ->assertRedirect(route('login'));
    }

    public function test_candidate_cannot_access_procedure_minutes_index(): void
    {
        $this->actingAs($this->userWithRole('candidate'))
            ->get(route('backoffice.procedure-minutes.index'))
            ->assertForbidden();
    }

    public function test_administrator_sees_procedure_minutes_page(): void
    {
        $this->actingAs($this->userWithRole('administrator'))
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.procedure-minutes.index'))
            ->assertOk()
            ->assertSee('Atas do procedimento')
            ->assertSee('Gerar ata');
    }

    public function test_administrator_generates_and_approves_alcanena_procedure_minute(): void
    {
        $admin = $this->userWithRole('administrator');
        [$contest, $application] = $this->procedureDataSet($admin);
        $template = $this->publishedTemplate($admin);

        $this->actingAs($admin)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.procedure-minutes.generate'), [
                'procedure_template_id' => $template->id,
                'contest_id' => $contest->id,
                'application_id' => $application->id,
                'subject' => 'Relatório preliminar do procedimento',
                'title' => 'Ata Alcanena de teste',
                'meeting_date' => '2026-07-08',
                'meeting_time' => '10:30',
                'meeting_location' => 'Paços do Concelho de Alcanena',
                'municipal_registry_number' => 'REG-2026-001',
                'municipal_process_number' => 'PROC-2026-001',
                'external_reference' => 'EXT-001',
                'legal_basis' => 'Regulamento municipal aplicável.',
                'deliberation_text' => 'O júri deliberou prosseguir para a fase seguinte.',
                'observations' => 'Ata gerada para teste automatizado.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $minute = ProcedureMinute::query()->firstOrFail();

        $this->assertSame(ProcedureMinuteStatus::Generated, $minute->status);
        $this->assertSame('Município de Alcanena', data_get($minute->payload, 'municipal.municipality_name'));
        $this->assertSame($contest->title, data_get($minute->payload, 'contest.title'));
        $this->assertSame(1, data_get($minute->payload, 'summary.applications_total'));
        $this->assertStringContainsString('Município de Alcanena', $minute->content_snapshot);
        $this->assertStringContainsString($contest->title, $minute->content_snapshot);
        $this->assertStringNotContainsString('{{contest_title}}', $minute->content_snapshot);
        $this->assertNotNull($minute->file_path);
        Storage::disk('local')->assertExists($minute->file_path);

        $this->actingAs($admin)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.procedure-minutes.approve', $minute))
            ->assertRedirect();

        $this->assertSame(ProcedureMinuteStatus::Approved, $minute->refresh()->status);

        $this->actingAs($admin)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.procedure-minutes.show', $minute))
            ->assertOk()
            ->assertDontSee('Aprovar ata');
    }

    public function test_alcanena_procedure_template_seeder_creates_active_templates(): void
    {
        $this->seed(AlcanenaProcedureTemplateSeeder::class);
        $this->seed(AlcanenaProcedureTemplateSeeder::class);

        $this->assertSame(1, ProcedureTemplate::query()->where('template_number', 'ALC-ATA-01-SERIACAO-INICIAL')->count());
        $this->assertSame(9, ProcedureTemplate::query()->where('type', ProcedureTemplateType::ProcedureMinute)->where('status', ProcedureTemplateStatus::Active)->count());
        $this->assertDatabaseHas('procedure_templates', [
            'template_number' => 'ALC-ATA-10-RELATORIO-FINAL-ATRIBUICAO',
            'name' => 'Ata 10 — Relatório final de atribuição',
        ]);
    }

    /**
     * @return array{0: Contest, 1: Application}
     */
    private function procedureDataSet(User $admin): array
    {
        $program = Program::factory()->create([
            'municipality_id' => $admin->municipality_id,
            'name' => 'Arrendamento Municipal Acessível',
        ]);
        $contest = Contest::factory()->create([
            'program_id' => $program->id,
            'code' => '01/2026',
            'title' => 'Arrendamento Municipal Acessível de Alcanena',
        ]);
        $candidate = User::factory()->create([
            'municipality_id' => $admin->municipality_id,
            'name' => 'Candidato Alcanena',
        ]);
        $application = Application::factory()
            ->submitted()
            ->create([
                'program_id' => $program->id,
                'contest_id' => $contest->id,
                'user_id' => $candidate->id,
            ]);
        $housingUnit = HousingUnit::factory()->create([
            'municipality_id' => $admin->municipality_id,
            'code' => 'ALC-T2-001',
            'typology' => 'T2',
            'locality' => 'Alcanena',
        ]);

        ContestHousingUnit::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'housing_unit_id' => $housingUnit->id,
        ]);

        return [$contest, $application];
    }

    private function publishedTemplate(User $publisher): ProcedureTemplate
    {
        return ProcedureTemplate::factory()->create([
            'type' => ProcedureTemplateType::ProcedureMinute,
            'status' => ProcedureTemplateStatus::Active,
            'name' => 'Ata Alcanena teste',
            'content' => '<p>{{municipality_name}} · {{contest_title}} · {{contest_applications_total}} · {{legal_basis}}</p>',
            'variables' => ['municipality_name', 'contest_title', 'contest_applications_total', 'legal_basis'],
            'created_by' => $publisher->id,
            'published_by' => $publisher->id,
            'published_at' => now(),
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
