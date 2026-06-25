<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QA42WcagCandidatePublicAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_layout_exposes_skip_link_main_landmark_and_heading(): void
    {
        $this->get(route('public.portal'))
            ->assertOk()
            ->assertSee('Saltar para o conteúdo principal')
            ->assertSee('href="#conteudo-principal"', false)
            ->assertSee('id="conteudo-principal"', false)
            ->assertSee('<main', false)
            ->assertSee('<h1', false);
    }

    public function test_guest_login_layout_exposes_skip_link_and_main_landmark(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Saltar para o conteúdo principal')
            ->assertSee('id="conteudo-principal"', false)
            ->assertSee('<main', false);
    }

    public function test_candidate_dashboard_exposes_keyboard_skip_target_and_semantic_heading(): void
    {
        $this->seed(SystemAccessSeeder::class);

        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $this->actingAs($candidate)
            ->get(route('candidate.dashboard'))
            ->assertOk()
            ->assertSee('Saltar para o conteúdo principal')
            ->assertSee('id="conteudo-principal"', false)
            ->assertSee('Área do Candidato')
            ->assertSee('<h1', false);
    }
}
