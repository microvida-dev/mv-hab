<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentReferencePeriodUnit;
use App\Models\DocumentSubmission;
use App\Models\RequiredDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RepeatableDocumentRequirementFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeatable_document_requirement_schema_is_available(): void
    {
        $this->assertTrue(Schema::hasColumns('required_documents', [
            'required_submissions',
            'reference_period_unit',
            'requires_distinct_reference_periods',
            'reference_period_recency',
        ]));

        $this->assertTrue(Schema::hasColumns('document_submissions', [
            'requirement_instance',
            'reference_period',
        ]));
    }

    public function test_existing_required_document_factory_preserves_single_submission_defaults(): void
    {
        $requiredDocument = RequiredDocument::factory()->create();

        $this->assertSame(1, $requiredDocument->required_submissions);
        $this->assertNull($requiredDocument->reference_period_unit);
        $this->assertFalse($requiredDocument->requires_distinct_reference_periods);
        $this->assertNull($requiredDocument->reference_period_recency);
    }

    public function test_repeatable_requirement_configuration_is_cast_correctly(): void
    {
        $requiredDocument = RequiredDocument::factory()->create([
            'required_submissions' => 3,
            'reference_period_unit' => DocumentReferencePeriodUnit::Month->value,
            'requires_distinct_reference_periods' => true,
            'reference_period_recency' => 3,
        ])->refresh();

        $this->assertSame(3, $requiredDocument->required_submissions);
        $this->assertSame(
            DocumentReferencePeriodUnit::Month,
            $requiredDocument->reference_period_unit,
        );
        $this->assertTrue($requiredDocument->requires_distinct_reference_periods);
        $this->assertSame(3, $requiredDocument->reference_period_recency);
    }

    public function test_document_submission_instance_and_reference_period_are_cast_correctly(): void
    {
        $submission = DocumentSubmission::factory()->create([
            'requirement_instance' => 2,
            'reference_period' => '2026-06-01',
        ])->refresh();

        $this->assertSame(2, $submission->requirement_instance);
        $this->assertSame(
            '2026-06-01',
            $submission->reference_period?->toDateString(),
        );
    }
}
