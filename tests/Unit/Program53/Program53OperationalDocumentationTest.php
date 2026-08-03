<?php

namespace Tests\Unit\Program53;

use App\Enums\ApplicationResultChangeType;
use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\ApplicationReviewPublicationStatus;
use App\Enums\ApplicationReviewStatus;
use App\Enums\ApplicationStatus;
use App\Enums\ContestApplicationPhase;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionResponseStatus;
use App\Enums\CorrectionRevalidationAggregateResult;
use App\Enums\Program53FailureCode;
use App\Enums\Program53FailureDisposition;
use App\Enums\Program53HealthSeverity;
use App\Enums\ReportExportStatus;
use SimpleXMLElement;
use Tests\TestCase;

class Program53OperationalDocumentationTest extends TestCase
{
    public function test_bpmn_is_well_formed_and_covers_operational_participants(): void
    {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_file(
            base_path('docs/operations/program-53-process.bpmn'),
        );
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertInstanceOf(SimpleXMLElement::class, $xml);
        $this->assertSame([], $errors);

        $xml->registerXPathNamespace(
            'bpmn',
            'http://www.omg.org/spec/BPMN/20100524/MODEL',
        );
        $this->assertCount(1, $xml->xpath('//bpmn:process'));
        $this->assertCount(4, $xml->xpath('//bpmn:lane'));

        $contents = $this->document('docs/operations/program-53-process.bpmn');
        foreach ([
            'Candidato',
            'Técnico municipal',
            'Sistema MV-HAB',
            'Filas e workers',
            'Selar lote inicial',
            'Publicar resultados simultaneamente',
            'Revalidar apenas alterações',
            'Gerar snapshot, formatos e pacote',
        ] as $expected) {
            $this->assertStringContainsString($expected, $contents);
        }
    }

    public function test_state_matrix_documents_every_persisted_state_code(): void
    {
        $matrix = $this->document(
            'docs/operations/program-53-state-matrix.md',
        );

        $this->assertEnumValuesDocumented($matrix, [
            ApplicationStatus::class,
            ContestApplicationPhase::class,
            ApplicationReviewStatus::class,
            ApplicationReviewBatchCycle::class,
            ApplicationReviewBatchStatus::class,
            ApplicationReviewBatchOutcome::class,
            ApplicationReviewPublicationStatus::class,
            CorrectionRequestStatus::class,
            CorrectionResponseStatus::class,
            CorrectionResponseReviewResult::class,
            CorrectionRevalidationAggregateResult::class,
            ReportExportStatus::class,
        ]);

        foreach ([
            'Origem',
            'Transição',
            'Ator',
            'Permission',
            'Entitlement',
            'Evento',
            'Idempotência',
            'Terminal',
        ] as $column) {
            $this->assertStringContainsString($column, $matrix);
        }
    }

    public function test_export_catalog_documents_every_canonical_code(): void
    {
        $catalog = $this->document(
            'docs/operations/program-53-export-code-catalog.md',
        );

        $this->assertEnumValuesDocumented($catalog, [
            ApplicationResultExportMode::class,
            ApplicationResultExportDataset::class,
            ApplicationResultChangeType::class,
            ApplicationReviewBatchCycle::class,
            ApplicationReviewBatchStatus::class,
            ApplicationReviewBatchOutcome::class,
            ApplicationReviewPublicationStatus::class,
            CorrectionRequestStatus::class,
            CorrectionResponseStatus::class,
            CorrectionResponseReviewResult::class,
            CorrectionRevalidationAggregateResult::class,
            ReportExportStatus::class,
            Program53FailureCode::class,
            Program53FailureDisposition::class,
            Program53HealthSeverity::class,
        ]);

        $this->assertStringContainsString('CSV', $catalog);
        $this->assertStringContainsString('JSON', $catalog);
        $this->assertStringContainsString('XML', $catalog);
        $this->assertStringContainsString('XLSX', $catalog);
        $this->assertStringContainsString('schema `1.0`', $catalog);
    }

    public function test_closure_artifacts_exist_and_sprint_report_has_48_sections(): void
    {
        $paths = [
            'docs/programs/program-53-closure-report.md',
            'docs/operations/program-53-process.bpmn',
            'docs/operations/program-53-process.md',
            'docs/operations/program-53-state-matrix.md',
            'docs/operations/program-53-export-code-catalog.md',
            'docs/operations/program-53-analyst-manual.md',
            'docs/operations/program-53-deadline-configuration-manual.md',
            'docs/operations/program-53-queue-runbook.md',
            'docs/operations/program-53-retention-policy.md',
            'docs/operations/program-53-observability-runbook.md',
            'docs/operations/program-53-failure-recovery-matrix.md',
            'docs/quality/program-53-performance-report.md',
        ];

        foreach ($paths as $path) {
            $this->assertFileExists(base_path($path));
            $this->assertNotSame('', trim($this->document($path)));
        }

        $report = $this->document(
            'docs/04-sprints/sprint-53i-bulk-review-operational-hardening.md',
        );
        preg_match_all('/^## ([1-9]|[1-4][0-9])\. /m', $report, $matches);

        $this->assertSame(
            range(1, 48),
            array_map('intval', $matches[1]),
        );
    }

    /**
     * @param  list<class-string<\BackedEnum>>  $enums
     */
    private function assertEnumValuesDocumented(
        string $document,
        array $enums,
    ): void {
        foreach ($enums as $enum) {
            foreach ($enum::cases() as $case) {
                $this->assertStringContainsString(
                    '`'.$case->value.'`',
                    $document,
                    sprintf(
                        'O código %s::%s não está documentado.',
                        $enum,
                        $case->name,
                    ),
                );
            }
        }
    }

    private function document(string $path): string
    {
        $contents = file_get_contents(base_path($path));
        $this->assertIsString($contents);

        return $contents;
    }
}
