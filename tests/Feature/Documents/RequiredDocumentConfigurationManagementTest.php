<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentReferencePeriodUnit;
use App\Enums\IncomeSourceType;
use App\Enums\RequiredDocumentConditionOperator;
use App\Models\Contest;
use App\Models\DocumentType;
use App\Models\Program;
use App\Models\RequiredDocument;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequiredDocumentConfigurationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);

        $this->administrator = User::factory()->create();
        $this->administrator->assignRole('administrator');
    }

    public function test_administrator_can_create_a_repeatable_monthly_requirement(): void
    {
        $documentType = $this->documentType();

        $this->actingAsAdministrator()
            ->post(
                route('admin.required-documents.store'),
                $this->validPayload($documentType),
            )
            ->assertRedirect(
                route('admin.required-documents.index'),
            )
            ->assertSessionHas('success');

        $this->assertDatabaseHas('required_documents', [
            'document_type_id' => $documentType->id,
            'program_id' => null,
            'contest_id' => null,
            'required_for' => DocumentAppliesTo::IncomeRecord->value,
            'condition_key' => 'income_record.income_source',
            'condition_operator' => RequiredDocumentConditionOperator::Equals->value,
            'condition_value' => IncomeSourceType::Employment->value,
            'required_submissions' => 3,
            'reference_period_unit' => DocumentReferencePeriodUnit::Month->value,
            'requires_distinct_reference_periods' => true,
            'reference_period_recency' => 3,
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 70,
        ]);
    }

    public function test_administrator_can_update_repeatable_configuration(): void
    {
        $documentType = $this->documentType();

        $requiredDocument = RequiredDocument::factory()->create([
            'document_type_id' => $documentType->id,
            'required_submissions' => 1,
            'reference_period_unit' => null,
            'requires_distinct_reference_periods' => false,
            'reference_period_recency' => null,
        ]);

        $this->actingAsAdministrator()
            ->patch(
                route(
                    'admin.required-documents.update',
                    $requiredDocument,
                ),
                $this->validPayload($documentType, [
                    'instructions' => 'Submeta os três recibos de vencimento mais recentes.',
                    'sort_order' => 80,
                ]),
            )
            ->assertRedirect(
                route('admin.required-documents.index'),
            );

        $requiredDocument->refresh();

        $this->assertSame(
            3,
            $requiredDocument->required_submissions,
        );

        $this->assertSame(
            DocumentReferencePeriodUnit::Month,
            $requiredDocument->reference_period_unit,
        );

        $this->assertTrue(
            $requiredDocument->requires_distinct_reference_periods,
        );

        $this->assertSame(
            3,
            $requiredDocument->reference_period_recency,
        );

        $this->assertSame(
            'Submeta os três recibos de vencimento mais recentes.',
            $requiredDocument->instructions,
        );

        $this->assertSame(80, $requiredDocument->sort_order);
    }

    public function test_distinct_periods_require_a_periodicity(): void
    {
        $documentType = $this->documentType();

        $this->actingAsAdministrator()
            ->from(route('admin.required-documents.create'))
            ->post(
                route('admin.required-documents.store'),
                $this->validPayload($documentType, [
                    'reference_period_unit' => null,
                    'requires_distinct_reference_periods' => true,
                    'reference_period_recency' => null,
                ]),
            )
            ->assertRedirect(
                route('admin.required-documents.create'),
            )
            ->assertSessionHasErrors('reference_period_unit');

        $this->assertDatabaseCount('required_documents', 0);
    }

    public function test_distinct_periods_require_at_least_two_submissions(): void
    {
        $documentType = $this->documentType();

        $this->actingAsAdministrator()
            ->post(
                route('admin.required-documents.store'),
                $this->validPayload($documentType, [
                    'required_submissions' => 1,
                    'requires_distinct_reference_periods' => true,
                ]),
            )
            ->assertSessionHasErrors(
                'requires_distinct_reference_periods',
            );

        $this->assertDatabaseCount('required_documents', 0);
    }

    public function test_recency_requires_a_periodicity(): void
    {
        $documentType = $this->documentType();

        $this->actingAsAdministrator()
            ->post(
                route('admin.required-documents.store'),
                $this->validPayload($documentType, [
                    'reference_period_unit' => null,
                    'requires_distinct_reference_periods' => false,
                    'reference_period_recency' => 3,
                ]),
            )
            ->assertSessionHasErrors('reference_period_unit');

        $this->assertDatabaseCount('required_documents', 0);
    }

    public function test_contest_must_belong_to_selected_program(): void
    {
        $documentType = $this->documentType();

        $contestProgram = Program::factory()->create();
        $differentProgram = Program::factory()->create();

        $contest = Contest::factory()
            ->for($contestProgram)
            ->create();

        $this->actingAsAdministrator()
            ->post(
                route('admin.required-documents.store'),
                $this->validPayload($documentType, [
                    'program_id' => $differentProgram->id,
                    'contest_id' => $contest->id,
                ]),
            )
            ->assertSessionHasErrors('contest_id');

        $this->assertDatabaseCount('required_documents', 0);
    }

    public function test_contest_scope_automatically_inherits_its_program(): void
    {
        $documentType = $this->documentType();

        $program = Program::factory()->create();

        $contest = Contest::factory()
            ->for($program)
            ->create();

        $this->actingAsAdministrator()
            ->post(
                route('admin.required-documents.store'),
                $this->validPayload($documentType, [
                    'program_id' => null,
                    'contest_id' => $contest->id,
                ]),
            )
            ->assertRedirect(
                route('admin.required-documents.index'),
            );

        $this->assertDatabaseHas('required_documents', [
            'document_type_id' => $documentType->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
        ]);
    }

    public function test_create_and_index_pages_expose_repeatable_configuration(): void
    {
        $documentType = $this->documentType();

        RequiredDocument::factory()->create([
            'document_type_id' => $documentType->id,
            'required_submissions' => 3,
            'reference_period_unit' => DocumentReferencePeriodUnit::Month,
            'requires_distinct_reference_periods' => true,
            'reference_period_recency' => 3,
        ]);

        $this->actingAsAdministrator()
            ->get(route('admin.required-documents.create'))
            ->assertOk()
            ->assertSee('Número de submissões')
            ->assertSee('Periodicidade')
            ->assertSee('Antiguidade máxima')
            ->assertSee('Exigir períodos distintos');

        $this->actingAsAdministrator()
            ->get(route('admin.required-documents.index'))
            ->assertOk()
            ->assertSee($documentType->name)
            ->assertSee('Submissões')
            ->assertSee('Periodicidade')
            ->assertSee('Períodos distintos')
            ->assertSee('3');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(
        DocumentType $documentType,
        array $overrides = [],
    ): array {
        return array_replace([
            'document_type_id' => $documentType->id,
            'program_id' => null,
            'contest_id' => null,
            'required_for' => DocumentAppliesTo::IncomeRecord->value,
            'condition_key' => 'income_record.income_source',
            'condition_operator' => RequiredDocumentConditionOperator::Equals->value,
            'condition_value' => IncomeSourceType::Employment->value,
            'required_submissions' => 3,
            'reference_period_unit' => DocumentReferencePeriodUnit::Month->value,
            'requires_distinct_reference_periods' => true,
            'reference_period_recency' => 3,
            'is_required' => true,
            'is_active' => true,
            'instructions' => 'Comprovativos mensais de rendimentos de trabalho.',
            'sort_order' => 70,
        ], $overrides);
    }

    private function documentType(): DocumentType
    {
        return DocumentType::factory()->create([
            'name' => 'Recibos de vencimento',
            'code' => 'recibos_vencimento_teste',
            'applies_to' => DocumentAppliesTo::IncomeRecord,
            'is_active' => true,
        ]);
    }

    private function actingAsAdministrator(): static
    {
        return $this
            ->actingAs($this->administrator)
            ->withSession([
                'mfa.verified_at' => now(),
            ]);
    }
}
