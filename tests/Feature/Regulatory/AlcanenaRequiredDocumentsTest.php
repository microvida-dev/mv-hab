<?php

namespace Tests\Feature\Regulatory;

use App\Enums\DocumentReferencePeriodUnit;
use App\Models\Contest;
use App\Models\RequiredDocument;
use App\Services\Documents\RequiredDocumentResolver;
use Database\Seeders\DemoAlcanenaAffordableRentSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\RequiredDocumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlcanenaRequiredDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_documents_are_active_and_linked_to_the_alcanena_contest(): void
    {
        $this->seed(
            DemoAlcanenaAffordableRentSeeder::class,
        );

        $contest = Contest::query()
            ->where(
                'code',
                DemoAlcanenaAffordableRentSeeder::CONTEST_CODE,
            )
            ->firstOrFail();

        $documents = RequiredDocument::query()
            ->where('contest_id', $contest->id)
            ->with('documentType')
            ->get();

        $this->assertCount(12, $documents);

        $this->assertTrue(
            $documents->every(
                fn (RequiredDocument $document): bool => $document->is_active
                    && $document->is_required,
            ),
        );

        $codes = $documents
            ->pluck('documentType.code')
            ->filter()
            ->values();

        $this->assertSame(
            11,
            $codes
                ->filter(
                    fn (string $code): bool => str_starts_with($code, 'alcanena_'),
                )
                ->count(),
        );

        $this->assertContains(
            'recibos_vencimento',
            $codes->all(),
        );

        $payslipRequirement = $documents->first(
            fn (RequiredDocument $document): bool => $document->documentType?->code
                    === 'recibos_vencimento',
        );

        $this->assertInstanceOf(
            RequiredDocument::class,
            $payslipRequirement,
        );

        $this->assertSame(
            3,
            $payslipRequirement->required_submissions,
        );

        $this->assertSame(
            DocumentReferencePeriodUnit::Month,
            $payslipRequirement->reference_period_unit,
        );

        $this->assertTrue(
            $payslipRequirement
                ->requires_distinct_reference_periods,
        );

        $this->assertSame(
            3,
            $payslipRequirement
                ->reference_period_recency,
        );

        $this->assertSame(
            10,
            $documents
                ->sortBy('sort_order')
                ->first()
                ?->sort_order,
        );
    }

    public function test_contest_payslip_rule_overrides_the_global_rule_without_duplication(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(RequiredDocumentSeeder::class);

        $this->seed(
            DemoAlcanenaAffordableRentSeeder::class,
        );

        $contest = Contest::query()
            ->where(
                'code',
                DemoAlcanenaAffordableRentSeeder::CONTEST_CODE,
            )
            ->with('program')
            ->firstOrFail();

        $resolved = app(RequiredDocumentResolver::class)
            ->resolve(
                programId: $contest->program_id,
                contestId: $contest->id,
            );

        $payslipRequirements = $resolved->filter(
            fn (RequiredDocument $requirement): bool => $requirement->documentType?->code
                    === 'recibos_vencimento',
        );

        $this->assertCount(1, $payslipRequirements);

        $requirement = $payslipRequirements->firstOrFail();

        $this->assertSame(
            $contest->id,
            $requirement->contest_id,
        );

        $this->assertSame(
            3,
            $requirement->required_submissions,
        );
    }
}
