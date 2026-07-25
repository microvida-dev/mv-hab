<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentAppliesTo;
use App\Enums\RequiredDocumentConditionOperator;
use App\Models\AdhesionRegistration;
use App\Models\Contest;
use App\Models\DocumentType;
use App\Models\Program;
use App\Models\RequiredDocument;
use App\Models\User;
use App\Services\Documents\DocumentChecklistService;
use App\Services\Documents\RequiredDocumentResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequiredDocumentResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_contest_rule_overrides_program_and_global_equivalent_rules(): void
    {
        $documentType = $this->documentType();

        $program = Program::factory()->create();

        $contest = Contest::factory()
            ->for($program)
            ->create();

        $globalRule = $this->rule(
            documentType: $documentType,
            overrides: [
                'required_submissions' => 1,
                'instructions' => 'Configuração global.',
            ],
        );

        $programRule = $this->rule(
            documentType: $documentType,
            program: $program,
            overrides: [
                'required_submissions' => 2,
                'instructions' => 'Configuração do programa.',
            ],
        );

        $contestRule = $this->rule(
            documentType: $documentType,
            program: $program,
            contest: $contest,
            overrides: [
                'required_submissions' => 3,
                'instructions' => 'Configuração do concurso.',
            ],
        );

        $resolved = app(RequiredDocumentResolver::class)
            ->resolve(
                programId: $program->id,
                contestId: $contest->id,
            );

        $this->assertCount(1, $resolved);
        $this->assertSame(
            $contestRule->id,
            $resolved->first()?->id,
        );
        $this->assertSame(
            3,
            $resolved->first()?->required_submissions,
        );

        $this->assertNotContains(
            $globalRule->id,
            $resolved->pluck('id')->all(),
        );

        $this->assertNotContains(
            $programRule->id,
            $resolved->pluck('id')->all(),
        );
    }

    public function test_program_rule_overrides_global_rule_without_contest_override(): void
    {
        $documentType = $this->documentType();
        $program = Program::factory()->create();

        $this->rule(
            documentType: $documentType,
            overrides: [
                'required_submissions' => 1,
            ],
        );

        $programRule = $this->rule(
            documentType: $documentType,
            program: $program,
            overrides: [
                'required_submissions' => 2,
            ],
        );

        $resolved = app(RequiredDocumentResolver::class)
            ->resolve(programId: $program->id);

        $this->assertCount(1, $resolved);
        $this->assertSame(
            $programRule->id,
            $resolved->first()?->id,
        );
        $this->assertSame(
            2,
            $resolved->first()?->required_submissions,
        );
    }

    public function test_rules_with_different_functional_identity_are_preserved(): void
    {
        $documentType = $this->documentType();
        $program = Program::factory()->create();

        $globalEmploymentRule = $this->rule(
            documentType: $documentType,
            overrides: [
                'condition_key' => 'income_record.income_source',
                'condition_operator' => RequiredDocumentConditionOperator::Equals,
                'condition_value' => 'employment',
            ],
        );

        $globalPensionRule = $this->rule(
            documentType: $documentType,
            overrides: [
                'condition_key' => 'income_record.income_source',
                'condition_operator' => RequiredDocumentConditionOperator::Equals,
                'condition_value' => 'pension',
            ],
        );

        $programEmploymentRule = $this->rule(
            documentType: $documentType,
            program: $program,
            overrides: [
                'condition_key' => 'income_record.income_source',
                'condition_operator' => RequiredDocumentConditionOperator::Equals,
                'condition_value' => 'employment',
                'required_submissions' => 3,
            ],
        );

        $resolved = app(RequiredDocumentResolver::class)
            ->resolve(programId: $program->id);

        $resolvedIds = $resolved->pluck('id')->all();

        $this->assertCount(2, $resolved);
        $this->assertContains(
            $programEmploymentRule->id,
            $resolvedIds,
        );
        $this->assertContains(
            $globalPensionRule->id,
            $resolvedIds,
        );
        $this->assertNotContains(
            $globalEmploymentRule->id,
            $resolvedIds,
        );
    }

    public function test_unrelated_scopes_and_inactive_configuration_are_excluded(): void
    {
        $activeType = $this->documentType();

        $inactiveType = $this->documentType([
            'code' => 'inactive_document_type',
            'name' => 'Tipo documental inativo',
            'is_active' => false,
        ]);

        $selectedProgram = Program::factory()->create();
        $unrelatedProgram = Program::factory()->create();

        $activeGlobalRule = $this->rule(
            documentType: $activeType,
        );

        $this->rule(
            documentType: $activeType,
            program: $unrelatedProgram,
        );

        $this->rule(
            documentType: $inactiveType,
            overrides: [
                'condition_key' => 'inactive_type',
            ],
        );

        $this->rule(
            documentType: $activeType,
            overrides: [
                'condition_key' => 'inactive_rule',
                'is_active' => false,
            ],
        );

        $resolved = app(RequiredDocumentResolver::class)
            ->resolve(programId: $selectedProgram->id);

        $this->assertCount(1, $resolved);
        $this->assertSame(
            $activeGlobalRule->id,
            $resolved->first()?->id,
        );
    }

    public function test_checklist_uses_only_the_effective_contest_rule(): void
    {
        $candidate = User::factory()->create();

        $registration = AdhesionRegistration::factory()
            ->registered()
            ->for($candidate)
            ->create();

        $documentType = $this->documentType();
        $program = Program::factory()->create();

        $contest = Contest::factory()
            ->for($program)
            ->create();

        $this->rule(
            documentType: $documentType,
            overrides: [
                'required_submissions' => 1,
            ],
        );

        $this->rule(
            documentType: $documentType,
            program: $program,
            overrides: [
                'required_submissions' => 2,
            ],
        );

        $contestRule = $this->rule(
            documentType: $documentType,
            program: $program,
            contest: $contest,
            overrides: [
                'required_submissions' => 3,
            ],
        );

        $checklist = app(DocumentChecklistService::class)
            ->forRegistration(
                registration: $registration,
                application: null,
                program: $program,
                contest: $contest,
            );

        $items = collect($checklist['items']);

        $this->assertSame(
            3,
            $checklist['summary']['total_required'],
        );

        $this->assertSame(
            [1, 2, 3],
            $items
                ->pluck('requirement_instance')
                ->all(),
        );

        $this->assertSame(
            [$contestRule->id],
            $items
                ->pluck('required_document_id')
                ->unique()
                ->values()
                ->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function documentType(
        array $overrides = [],
    ): DocumentType {
        return DocumentType::factory()->create(
            array_replace([
                'code' => 'documento_resolucao_teste',
                'name' => 'Documento de resolução',
                'applies_to' => DocumentAppliesTo::AdhesionRegistration,
                'is_active' => true,
            ], $overrides),
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function rule(
        DocumentType $documentType,
        ?Program $program = null,
        ?Contest $contest = null,
        array $overrides = [],
    ): RequiredDocument {
        return RequiredDocument::factory()->create(
            array_replace([
                'document_type_id' => $documentType->id,
                'program_id' => $program?->id,
                'contest_id' => $contest?->id,
                'required_for' => DocumentAppliesTo::AdhesionRegistration,
                'condition_key' => 'always',
                'condition_operator' => RequiredDocumentConditionOperator::Always,
                'condition_value' => null,
                'required_submissions' => 1,
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 10,
            ], $overrides),
        );
    }
}
