<?php

namespace Tests\Feature\Candidate;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_area_keeps_visible_focus_and_main_landmark(): void
    {
        $this->seed(SystemAccessSeeder::class);

        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $this->actingAs($candidate)
            ->get(route('candidate.dashboard'))
            ->assertOk()
            ->assertSee('id="conteudo-principal"', false)
            ->assertSee('Saltar para o conteúdo principal');

        $css = (string) file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('focus-visible:ring-civic-500', $css);
    }
}
