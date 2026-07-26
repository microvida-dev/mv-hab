<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentReferencePeriodUnit;
use App\Models\RequiredDocument;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\RequiredDocumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequiredDocumentSeederRepeatableConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_payslip_requirement_is_repeatable_and_idempotent(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(RequiredDocumentSeeder::class);
        $this->seed(RequiredDocumentSeeder::class);

        $requirements = RequiredDocument::query()
            ->whereNull('program_id')
            ->whereNull('contest_id')
            ->whereHas(
                'documentType',
                fn ($query) => $query
                    ->where('code', 'recibos_vencimento'),
            )
            ->get();

        $this->assertCount(1, $requirements);

        $requirement = $requirements->firstOrFail();

        $this->assertSame(
            3,
            $requirement->required_submissions,
        );

        $this->assertSame(
            DocumentReferencePeriodUnit::Month,
            $requirement->reference_period_unit,
        );

        $this->assertTrue(
            $requirement
                ->requires_distinct_reference_periods,
        );

        $this->assertSame(
            3,
            $requirement->reference_period_recency,
        );

        $this->assertStringContainsString(
            'três recibos de vencimento',
            (string) $requirement->instructions,
        );
    }

    public function test_other_global_requirements_remain_single_and_non_periodic(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(RequiredDocumentSeeder::class);

        $requirements = RequiredDocument::query()
            ->whereNull('program_id')
            ->whereNull('contest_id')
            ->whereHas(
                'documentType',
                fn ($query) => $query
                    ->where('code', '!=', 'recibos_vencimento'),
            )
            ->get();

        $this->assertNotEmpty($requirements);

        $this->assertTrue(
            $requirements->every(
                fn (RequiredDocument $requirement): bool => $requirement->required_submissions === 1
                    && $requirement->reference_period_unit === null
                    && ! $requirement
                        ->requires_distinct_reference_periods
                    && $requirement
                        ->reference_period_recency === null,
            ),
        );
    }
}
