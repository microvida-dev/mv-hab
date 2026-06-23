<?php

namespace Tests\Feature\Backoffice;

use App\Enums\DocumentAiValidationGroup;
use App\Enums\DocumentAiValidationSeverity;
use App\Enums\DocumentAiValidationStatus;
use App\Models\Application;
use App\Models\DocumentAiValidation;
use App\Models\DocumentAiValidationRun;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentAiValidationPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_validation_panel_is_protected_and_visible_to_backoffice_only(): void
    {
        $run = DocumentAiValidationRun::factory()->create();

        $this->get(route('backoffice.document-ai.validations.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->userWithRole('candidate'))
            ->get(route('backoffice.document-ai.validations.index'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('administrator'))
            ->get(route('backoffice.document-ai.validations.index'))
            ->assertOk()
            ->assertSee('Validação IA documental')
            ->assertSee((string) $run->total_checks);
    }

    public function test_detail_masks_sensitive_values_for_regular_technicians_and_records_access(): void
    {
        [$application, $validation] = $this->validationRecord();

        $this->actingAs($this->userWithRole('municipal_technician'))
            ->get(route('backoffice.document-ai.validations.show', $application))
            ->assertOk()
            ->assertSee('Validação IA da candidatura')
            ->assertDontSee('Maria Silva Correia')
            ->assertSee('M***a');

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $validation->run->getMorphClass(),
            'auditable_id' => $validation->document_ai_validation_run_id,
            'action' => 'document_ai_candidate_validation_viewed',
        ]);
    }

    public function test_authorized_user_can_mark_validation_for_manual_review(): void
    {
        [, $validation] = $this->validationRecord();

        $this->actingAs($this->userWithRole('administrator'))
            ->post(route('backoffice.document-ai.validations.manual-review', $validation), [
                'review_notes' => 'Confirmacao manual necessaria para teste.',
            ])
            ->assertRedirect(route('backoffice.document-ai.validations.validation', $validation));

        $validation->refresh();

        $this->assertSame(DocumentAiValidationStatus::ManualReview, $validation->status);
        $this->assertTrue($validation->requires_manual_review);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $validation->getMorphClass(),
            'auditable_id' => $validation->id,
            'action' => 'document_ai_candidate_validation_marked_review',
        ]);
    }

    public function test_validation_panel_handles_records_without_severity(): void
    {
        [$application] = $this->validationRecord([
            'severity' => null,
            'requires_manual_review' => false,
        ]);

        $this->actingAs($this->userWithRole('administrator'))
            ->get(route('backoffice.document-ai.validations.show', $application))
            ->assertOk()
            ->assertSee('Validação IA da candidatura');
    }

    /**
     * @param  array<string, mixed>  $validationOverrides
     * @return array{0: Application, 1: DocumentAiValidation}
     */
    private function validationRecord(array $validationOverrides = []): array
    {
        $application = Application::factory()->submitted()->create([
            'application_number' => 'CAND-TEST-000001',
        ]);
        $run = DocumentAiValidationRun::factory()->create([
            'application_id' => $application->id,
            'status' => DocumentAiValidationStatus::ManualReview->value,
            'total_checks' => 1,
            'critical_count' => 1,
            'requires_manual_review' => true,
        ]);
        $validation = DocumentAiValidation::factory()->create(array_merge([
            'document_ai_validation_run_id' => $run->id,
            'application_id' => $application->id,
            'validation_group' => DocumentAiValidationGroup::Identification->value,
            'validation_key' => 'name',
            'label' => 'Nome coincide',
            'status' => DocumentAiValidationStatus::Mismatch->value,
            'severity' => DocumentAiValidationSeverity::Critical->value,
            'candidate_value' => 'Maria Silva Correia',
            'extracted_value' => 'Pessoa Diferente',
            'requires_manual_review' => true,
            'metadata' => ['sensitive' => true, 'health_data' => false],
        ], $validationOverrides));

        return [$application, $validation->fresh(['run']) ?? $validation];
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $roleModel = Role::query()->where('name', $role)->firstOrFail();
        $user->roles()->attach($roleModel);

        return $user;
    }
}
