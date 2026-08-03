<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentDossierItemStatus;
use App\Enums\DocumentReferencePeriodUnit;
use App\Enums\DocumentStatus;
use App\Enums\RequiredDocumentConditionOperator;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\DocumentDossier;
use App\Models\DocumentDossierItem;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\RequiredDocument;
use App\Services\Applications\HousingPreferenceSnapshotService;
use App\Services\Documents\DocumentChecklistService;
use App\Services\Documents\DocumentSubmissionContextResolver;
use App\Services\DocumentStandardization\DocumentDossierBuilder;
use App\Services\DocumentStandardization\DocumentDossierExportService;
use App\Services\DocumentStandardization\DocumentStandardizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class RepeatableDocumentDossierTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_preserves_three_formally_associated_slots(): void
    {
        $application = Application::factory()
            ->submitted()
            ->create([
                'application_number' => 'APP-DOSSIER-REPEATABLE-001',
            ]);

        $documentType = DocumentType::factory()->create([
            'code' => 'repeatable_dossier_test',
            'name' => 'Comprovativos mensais',
            'applies_to' => DocumentAppliesTo::Application,
            'is_active' => true,
        ]);

        $requirement = RequiredDocument::factory()->create([
            'document_type_id' => $documentType->id,
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
            'required_for' => DocumentAppliesTo::Application,
            'condition_key' => 'always',
            'condition_operator' => RequiredDocumentConditionOperator::Always,

            'condition_value' => null,
            'required_submissions' => 3,
            'reference_period_unit' => DocumentReferencePeriodUnit::Month,

            'requires_distinct_reference_periods' => true,
            'reference_period_recency' => 3,
            'is_required' => true,
            'is_active' => true,
        ]);

        $requirement->load('documentType');

        $months = [
            1 => '2026-05-01',
            2 => '2026-06-01',
            3 => '2026-07-01',
        ];

        /** @var Collection<int, DocumentSubmission> $submissions */
        $submissions = collect($months)
            ->map(function (
                string $month,
                int $instance,
            ) use (
                $application,
                $requirement,
                $documentType,
            ): DocumentSubmission {
                $submission = DocumentSubmission::factory()
                    ->forRequiredDocument($requirement)
                    ->create([
                        'application_id' => $application->id,
                        'user_id' => $application->user_id,
                        'document_type_id' => $documentType->id,
                        'requirement_instance' => $instance,
                        'reference_period' => $month,
                        'status' => DocumentStatus::Validated,
                    ]);

                ApplicationDocument::factory()->create([
                    'application_id' => $application->id,
                    'document_submission_id' => $submission->id,

                    'document_type_id' => $documentType->id,
                    'is_required' => true,
                    'status_at_submission' => DocumentStatus::Validated,
                ]);

                return $submission;
            });

        $checklistItems = $submissions
            ->map(function (
                DocumentSubmission $submission,
            ) use (
                $application,
                $requirement,
                $documentType,
            ): array {
                return [
                    'key' => implode('-', [
                        $requirement->id,
                        DocumentAppliesTo::Application->value,
                        $application->id,
                        $submission->requirement_instance,
                    ]),

                    'required_document' => $requirement,
                    'document_type' => $documentType,

                    'required_document_id' => $requirement->id,

                    'document_type_id' => $documentType->id,

                    'required_for' => DocumentAppliesTo::Application,

                    'group' => 'Documentos da candidatura',

                    'target_type' => DocumentAppliesTo::Application->value,

                    'target_id' => $application->id,

                    'target_label' => $application->application_number,

                    'application' => $application,
                    'instructions' => null,
                    'is_required' => true,

                    'requirement_instance' => $submission->requirement_instance,

                    'required_submissions' => 3,

                    'position_label' => $submission->requirement_instance.'/3',

                    'reference_period' => $submission->reference_period,

                    'submission' => $submission,
                    'status' => DocumentStatus::Validated,
                    'missing' => false,
                    'rejected' => false,
                    'validated' => true,
                ];
            })
            ->values();

        $checklist = Mockery::mock(
            DocumentChecklistService::class,
        );

        $checklist
            ->shouldReceive('forApplication')
            ->once()
            ->with(
                Mockery::on(
                    fn (Application $value): bool => $value->is($application),
                ),
            )
            ->andReturn([
                'items' => $checklistItems,
                'groups' => [],
                'summary' => [],
                'next_step' => null,
            ]);

        $builder = new DocumentDossierBuilder(
            checklist: $checklist,
            context: app(
                DocumentSubmissionContextResolver::class,
            ),
            standardization: app(DocumentStandardizationService::class),
            housingPreferences: app(HousingPreferenceSnapshotService::class),
        );

        $payload = $builder->build($application);

        $this->assertCount(3, $payload['items']);

        $this->assertSame(
            [1, 2, 3],
            collect($payload['items'])
                ->pluck('requirement_instance')
                ->all(),
        );

        $this->assertSame(
            ['1/3', '2/3', '3/3'],
            collect($payload['items'])
                ->pluck('position_label')
                ->all(),
        );

        $this->assertSame(
            [
                '2026-05-01',
                '2026-06-01',
                '2026-07-01',
            ],
            collect($payload['items'])
                ->pluck('reference_period')
                ->all(),
        );

        $this->assertSame(
            [
                $submissions->get(1)?->id,
                $submissions->get(2)?->id,
                $submissions->get(3)?->id,
            ],
            collect($payload['items'])
                ->pluck('document_submission_id')
                ->all(),
        );

        $this->assertTrue(
            collect($payload['items'])->every(
                fn (array $item): bool => $item['target_type']
                        === DocumentAppliesTo::Application->value
                    && $item['target_id'] === $application->id
                    && $item['required_submissions'] === 3
                    && $item['status']
                        === DocumentDossierItemStatus::Validated->value,
            ),
        );

        $this->assertSame([
            'missing' => 0,
            'rejected' => 0,
            'expired' => 0,
            'validated' => 3,
        ], $payload['summary']);
    }

    public function test_dossier_item_persists_repeatable_context(): void
    {
        $dossier = DocumentDossier::factory()->create();

        $item = new DocumentDossierItem([
            'category' => 'Rendimentos',
            'label' => 'Recibos de vencimento',
            'notes' => 'Posição documental mensal.',
        ]);

        $item->forceFill([
            'document_dossier_id' => $dossier->id,
            'target_type' => DocumentAppliesTo::IncomeRecord->value,

            'target_id' => 785,
            'target_label' => 'Trabalho dependente',
            'requirement_instance' => 2,
            'required_submissions' => 3,
            'reference_period' => '2026-06-01',
            'status' => DocumentDossierItemStatus::Validated,
            'sort_order' => 2,
            'is_required' => true,
            'is_missing' => false,
            'is_rejected' => false,
            'is_expired' => false,
            'is_validated' => true,
        ])->save();

        $item->refresh();

        $this->assertSame(
            DocumentAppliesTo::IncomeRecord->value,
            $item->target_type,
        );

        $this->assertSame(785, $item->target_id);
        $this->assertSame(2, $item->requirement_instance);
        $this->assertSame(3, $item->required_submissions);
        $this->assertSame('2/3', $item->positionLabel());
        $this->assertSame(
            '06/2026',
            $item->referencePeriodLabel(),
        );

        $this->assertDatabaseHas('document_dossier_items', [
            'id' => $item->id,
            'document_dossier_id' => $dossier->id,
            'target_type' => DocumentAppliesTo::IncomeRecord->value,

            'target_id' => 785,
            'requirement_instance' => 2,
            'required_submissions' => 3,
            'is_validated' => true,
        ]);

        $this->assertTrue(
            DocumentDossierItem::query()
                ->whereKey($item->id)
                ->whereDate(
                    'reference_period',
                    '2026-06-01',
                )
                ->exists(),
        );
    }

    public function test_export_exposes_target_position_and_reference_period(): void
    {
        Storage::fake('local');

        $dossier = DocumentDossier::factory()->create([
            'dossier_number' => 'DOS-2026-REPEATABLE-001',
            'title' => 'Dossier documental de teste',
        ]);

        $item = new DocumentDossierItem([
            'category' => 'Rendimentos',
            'label' => 'Recibos de vencimento',
            'notes' => 'Documento mensal.',
        ]);

        $item->forceFill([
            'document_dossier_id' => $dossier->id,
            'target_type' => DocumentAppliesTo::IncomeRecord->value,

            'target_id' => 785,
            'target_label' => 'Trabalho dependente',
            'requirement_instance' => 2,
            'required_submissions' => 3,
            'reference_period' => '2026-06-01',
            'status' => DocumentDossierItemStatus::Validated,
            'sort_order' => 2,
            'is_required' => true,
            'is_missing' => false,
            'is_rejected' => false,
            'is_expired' => false,
            'is_validated' => true,
        ])->save();

        $path = app(DocumentDossierExportService::class)
            ->export($dossier);

        Storage::disk('local')->assertExists($path);

        $contents = Storage::disk('local')->get($path);

        $this->assertStringContainsString(
            'Recibos de vencimento',
            $contents,
        );

        $this->assertStringContainsString(
            'Trabalho dependente',
            $contents,
        );

        $this->assertStringContainsString(
            '2/3',
            $contents,
        );

        $this->assertStringContainsString(
            '06/2026',
            $contents,
        );
    }
}
