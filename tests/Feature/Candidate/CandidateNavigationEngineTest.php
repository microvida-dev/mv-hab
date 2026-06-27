<?php

namespace Tests\Feature\Candidate;

use App\Enums\SimulationResultStatus;
use App\Models\Application;
use App\Models\Hearing;
use App\Models\SimulationSession;
use App\Models\TenantProfile;
use App\Models\User;
use App\Services\CandidateExperience\CandidateNavigationService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateNavigationEngineTest extends TestCase
{
    use RefreshDatabase;

    private CandidateNavigationService $navigation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->navigation = app(CandidateNavigationService::class);
    }

    public function test_newly_registered_candidate_sees_only_onboarding_simulator_and_contests(): void
    {
        $candidate = $this->candidate();

        $labels = $this->labels($candidate);

        foreach ([
            'Visão Geral',
            'O meu registo',
            'Agregado',
            'Rendimentos',
            'Habitação Atual',
            'Simulador',
            'Concursos',
        ] as $expected) {
            self::assertContains($expected, $labels);
        }

        foreach ([
            'Candidaturas',
            'Documentos',
            'Processo',
            'Interações',
            'Visitas',
            'Aperfeiçoamentos',
            'Elegibilidade',
            'Renovações',
            'Área do Inquilino',
        ] as $hidden) {
            self::assertNotContains($hidden, $labels);
        }

        self::assertEqualsCanonicalizing([
            'Ir para Portal Público',
            'Notificações',
        ], $this->footerLabels($candidate));

        self::assertNotContains('Preferências', $this->footerLabels($candidate));
        self::assertNotContains('Ajuda', $this->footerLabels($candidate));
    }

    public function test_candidate_footer_renders_only_public_portal_notifications_and_logout(): void
    {
        $candidate = $this->candidate();

        $this->actingAs($candidate)
            ->get(route('candidate.dashboard'))
            ->assertOk()
            ->assertSee('Ir para Portal Público')
            ->assertSee('Notificações')
            ->assertSee('Terminar sessão')
            ->assertDontSee('Preferências')
            ->assertDontSee('Ajuda');
    }

    public function test_eligible_simulation_reveals_eligibility_and_new_application_entry(): void
    {
        $candidate = $this->candidate();
        SimulationSession::factory()
            ->forCandidate($candidate)
            ->create(['result_status' => SimulationResultStatus::LikelyEligible->value]);

        $labels = $this->labels($candidate);

        self::assertContains('Elegibilidade', $labels);
        self::assertContains('Nova candidatura', $labels);
        self::assertNotContains('Consultar concursos', $labels);
        self::assertNotContains('Candidaturas', $labels);
    }

    public function test_ineligible_simulation_reveals_controlled_contest_consultation(): void
    {
        $candidate = $this->candidate();
        SimulationSession::factory()
            ->forCandidate($candidate)
            ->create(['result_status' => SimulationResultStatus::LikelyIneligible->value]);

        $labels = $this->labels($candidate);

        self::assertContains('Elegibilidade', $labels);
        self::assertContains('Consultar concursos', $labels);
        self::assertNotContains('Nova candidatura', $labels);
    }

    public function test_draft_application_reveals_application_documents_faq_and_support_only(): void
    {
        $candidate = $this->candidate();
        Application::factory()->create(['user_id' => $candidate->id]);

        $labels = $this->labels($candidate);

        foreach (['Candidaturas', 'Documentos', 'FAQ', 'Apoio'] as $expected) {
            self::assertContains($expected, $labels);
        }

        self::assertNotContains('Processo', $labels);
        self::assertNotContains('Interações', $labels);
        self::assertNotContains('Audiência Prévia', $labels);
        self::assertNotContains('Reclamações', $labels);
    }

    public function test_submitted_application_reveals_process_tracking_items(): void
    {
        $candidate = $this->candidate();
        Application::factory()->submitted()->create(['user_id' => $candidate->id]);

        $labels = $this->labels($candidate);

        foreach ([
            'Estado da candidatura',
            'Processo',
            'Documentos',
            'Interações',
            'Aperfeiçoamentos',
            'Visitas',
        ] as $expected) {
            self::assertContains($expected, $labels);
        }
    }

    public function test_provisional_list_stage_reveals_hearing_and_complaints(): void
    {
        $candidate = $this->candidate();
        $application = Application::factory()->submitted()->create(['user_id' => $candidate->id]);
        Hearing::factory()->create([
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'candidate_visible' => true,
        ]);

        $labels = $this->labels($candidate);

        self::assertContains('Audiência Prévia', $labels);
        self::assertContains('Reclamações', $labels);
    }

    public function test_tenant_access_replaces_candidate_navigation_with_tenant_area(): void
    {
        $candidate = $this->candidate();
        TenantProfile::factory()->create(['user_id' => $candidate->id]);

        $navigation = $this->navigation->forUser($candidate);
        $labels = $this->labels($candidate);

        self::assertSame('tenant.dashboard', $navigation['home_route']);

        foreach ([
            'Área do Inquilino',
            'Contratos',
            'Pagamentos',
            'Vistorias',
            'Manutenção',
            'Comunicações',
        ] as $expected) {
            self::assertContains($expected, $labels);
        }

        foreach ([
            'O meu registo',
            'Candidaturas',
            'Documentos',
            'Visitas',
        ] as $hidden) {
            self::assertNotContains($hidden, $labels);
        }
    }

    private function candidate(): User
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        return $candidate;
    }

    /**
     * @return list<string>
     */
    private function labels(User $candidate): array
    {
        return $this->collectLabels($this->navigation->forUser($candidate)['groups']);
    }

    /**
     * @return list<string>
     */
    private function footerLabels(User $candidate): array
    {
        return $this->collectLabels([$this->navigation->forUser($candidate)['footer']]);
    }

    /**
     * @param  array<string|int, list<array{label: string, route: string, active: string, icon: string}>>  $groups
     * @return list<string>
     */
    private function collectLabels(array $groups): array
    {
        $labels = [];

        foreach ($groups as $links) {
            foreach ($links as $link) {
                $labels[] = $link['label'];
            }
        }

        return $labels;
    }
}
