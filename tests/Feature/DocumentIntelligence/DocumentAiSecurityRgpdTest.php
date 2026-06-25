<?php

namespace Tests\Feature\DocumentIntelligence;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDocumentAiAssistantFixtures;
use Tests\TestCase;

class DocumentAiSecurityRgpdTest extends TestCase
{
    use CreatesDocumentAiAssistantFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_candidate_cannot_access_internal_document_ai_analysis_and_private_paths_are_hidden(): void
    {
        [,, $analysis] = $this->createAssistantAnalysis([
            'source_path' => 'private/documents/internal-only.pdf',
            'raw_ai_json' => ['raw' => 'payload must stay internal'],
        ]);

        $this->actingAs($this->userWithRole('candidate'))
            ->get(route('backoffice.document-ai.assistant.show', $analysis))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('administrator'))
            ->get(route('backoffice.document-ai.assistant.show', $analysis))
            ->assertOk()
            ->assertDontSee('private/documents/internal-only.pdf')
            ->assertDontSee('payload must stay internal');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
