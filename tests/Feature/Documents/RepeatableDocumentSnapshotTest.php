<?php

namespace Tests\Feature\Documents;

use App\Enums\ApplicationSnapshotType;
use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentReferencePeriodUnit;
use App\Enums\DocumentStatus;
use App\Enums\RequiredDocumentConditionOperator;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationSnapshot;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\RequiredDocument;
use App\Services\Applications\ApplicationSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepeatableDocumentSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_snapshot_preserves_repeatable_context(): void
    {
        $application = Application::factory()
            ->submitted()
            ->create([
                'application_number' => 'APP-SNAPSHOT-REPEATABLE-001',
            ]);

        $this->actingAs($application->user);

        $documentType = DocumentType::factory()->create([
            'code' => 'snapshot_repeatable_test',
            'name' => 'Recibos de vencimento',
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

        $submission = DocumentSubmission::factory()
            ->forRequiredDocument($requirement)
            ->create([
                'application_id' => $application->id,
                'adhesion_registration_id' => $application->adhesion_registration_id,

                'user_id' => $application->user_id,
                'document_type_id' => $documentType->id,
                'requirement_instance' => 2,
                'reference_period' => '2026-06-01',
                'status' => DocumentStatus::Validated,
            ]);

        ApplicationDocument::factory()->create([
            'application_id' => $application->id,
            'document_submission_id' => $submission->id,
            'document_type_id' => $documentType->id,
            'is_required' => true,
            'status_at_submission' => DocumentStatus::Validated,
        ]);

        app(ApplicationSnapshotService::class)
            ->create($application->fresh());

        $snapshot = ApplicationSnapshot::query()
            ->where('application_id', $application->id)
            ->where(
                'snapshot_type',
                ApplicationSnapshotType::Documents->value,
            )
            ->firstOrFail();

        $snapshotData = $snapshot->getAttribute('data');

        $this->assertIsArray($snapshotData);

        $document = collect($snapshotData)
            ->firstWhere(
                'document_submission_id',
                $submission->id,
            );

        $this->assertIsArray($document);

        $this->assertSame(
            $requirement->id,
            $document['required_document_id'],
        );

        $this->assertSame(
            DocumentAppliesTo::Application->value,
            $document['target_type'],
        );

        $this->assertSame(
            $application->id,
            $document['target_id'],
        );

        $this->assertSame(
            'APP-SNAPSHOT-REPEATABLE-001',
            $document['target_label'],
        );

        $this->assertSame(
            2,
            $document['requirement_instance'],
        );

        $this->assertSame(
            3,
            $document['required_submissions'],
        );

        $this->assertSame(
            '2/3',
            $document['position_label'],
        );

        $this->assertSame(
            '2026-06-01',
            $document['reference_period'],
        );
    }
}
