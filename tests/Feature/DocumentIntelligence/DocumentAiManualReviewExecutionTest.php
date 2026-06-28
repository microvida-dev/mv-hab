<?php

namespace Tests\Feature\DocumentIntelligence;

use App\Enums\DocumentAiStatus;
use App\Enums\DocumentStatus;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\Role;
use App\Models\User;
use App\Services\DocumentIntelligence\DocumentAiManualAnalysisService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DocumentAiManualReviewExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_document_review_page_exposes_document_ai_execution_action(): void
    {
        $submission = $this->documentSubmission();

        $this->actingAs($this->userWithRole('administrator'))
            ->get(route('admin.document-reviews.show', $submission))
            ->assertOk()
            ->assertSee('IA documental')
            ->assertSee('Executar IA documental')
            ->assertSee('Sem análise executada')
            ->assertSee('A análise IA é assistiva');
    }

    public function test_authorized_technician_can_execute_document_ai_without_changing_document_status(): void
    {
        $submission = $this->documentSubmission();
        $analysis = DocumentAiAnalysis::factory()->completed()->create([
            'document_submission_id' => $submission->id,
            'document_version_id' => $submission->current_version_id,
            'status' => DocumentAiStatus::Completed,
        ]);
        $technician = $this->userWithRole('municipal_technician');

        $this->mock(DocumentAiManualAnalysisService::class, function ($mock) use ($analysis, $submission, $technician): void {
            $mock->shouldReceive('execute')
                ->once()
                ->with(
                    Mockery::on(fn (DocumentSubmission $argument): bool => $argument->is($submission)),
                    Mockery::on(fn (User $argument): bool => $argument->is($technician)),
                )
                ->andReturn($analysis);
        });

        $this->actingAs($technician)
            ->post(route('admin.document-reviews.document-ai', $submission))
            ->assertRedirect(route('backoffice.document-ai.assistant.show', $analysis))
            ->assertSessionHas('success');

        $this->assertSame(DocumentStatus::Submitted, $submission->fresh()->status);
    }

    private function documentSubmission(): DocumentSubmission
    {
        $submission = DocumentSubmission::factory()->create([
            'status' => DocumentStatus::Submitted,
            'storage_disk' => 'local',
            'storage_path' => 'documents/test/documento-revisao-ia.pdf',
        ]);
        $version = DocumentVersion::factory()->create([
            'document_submission_id' => $submission->id,
            'storage_disk' => 'local',
            'storage_path' => 'documents/test/documento-revisao-ia.pdf',
        ]);
        $submission->forceFill([
            'current_version_id' => $version->id,
            'mime_type' => $version->mime_type,
            'file_size' => $version->file_size,
            'checksum' => $version->checksum,
        ])->save();

        return $submission->fresh('currentVersion') ?? $submission;
    }

    private function userWithRole(string $role): User
    {
        $this->assertTrue(Role::query()->where('name', $role)->exists());
        $user = User::factory()->create(['email' => 'document-ai-'.$role.'-'.fake()->unique()->numerify('####').'@example.test']);
        $user->assignRole($role);

        return $user;
    }
}
