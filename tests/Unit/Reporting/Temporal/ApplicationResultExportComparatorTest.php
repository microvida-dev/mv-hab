<?php

namespace Tests\Unit\Reporting\Temporal;

use App\Enums\ApplicationResultChangeType;
use App\Services\Reporting\Temporal\ApplicationResultExportComparator;
use RuntimeException;
use Tests\TestCase;

class ApplicationResultExportComparatorTest extends TestCase
{
    public function test_comparator_classifies_added_removed_and_changed_rows(): void
    {
        $changes = iterator_to_array(app(ApplicationResultExportComparator::class)->compare(
            baseRows: [
                $this->row('APP-001', 'PROC-001', 'submitted'),
                $this->row('APP-002', null, 'under_review'),
            ],
            targetRows: [
                $this->row('APP-001', 'PROC-001', 'eligible'),
                $this->row('APP-003', null, 'submitted'),
            ],
            entityType: 'application',
            keyFields: ['application_number', 'process_number'],
            comparedFields: ['submission_status_code'],
            beforeSource: 'batch-t1',
            afterSource: 'batch-t2',
            changedAt: '2026-08-01T12:00:00+00:00',
        ), false);

        $this->assertSame([
            ApplicationResultChangeType::Changed->value,
            ApplicationResultChangeType::Removed->value,
            ApplicationResultChangeType::Added->value,
        ], array_column($changes, 'change_type'));
        $this->assertSame(
            'under_review',
            $changes[1]['before_value']['submission_status_code'],
        );
        $this->assertSame(
            'submitted',
            $changes[2]['after_value']['submission_status_code'],
        );
    }

    public function test_comparator_redacts_personal_values_without_sensitive_permission(): void
    {
        $changes = iterator_to_array(app(ApplicationResultExportComparator::class)->compare(
            baseRows: [$this->row('APP-001', null, 'submitted', 'Nome anterior')],
            targetRows: [$this->row('APP-001', null, 'submitted', 'Nome posterior')],
            entityType: 'application',
            keyFields: ['application_number', 'process_number'],
            comparedFields: ['candidate_name'],
            beforeSource: 'batch-t1',
            afterSource: 'batch-t2',
            changedAt: '2026-08-01T12:00:00+00:00',
        ), false);

        $this->assertCount(1, $changes);
        $this->assertSame('[VALOR OCULTADO]', $changes[0]['before_value']);
        $this->assertSame('[VALOR OCULTADO]', $changes[0]['after_value']);
        $this->assertTrue($changes[0]['sensitive_value_redacted']);
    }

    public function test_comparator_can_emit_unchanged_and_supports_null_key_parts(): void
    {
        $changes = iterator_to_array(app(ApplicationResultExportComparator::class)->compare(
            baseRows: [$this->row('APP-001', null, 'submitted')],
            targetRows: [$this->row('APP-001', null, 'submitted')],
            entityType: 'application',
            keyFields: ['application_number', 'process_number'],
            comparedFields: ['submission_status_code'],
            beforeSource: 'batch-t1',
            afterSource: 'batch-t2',
            changedAt: '2026-08-01T12:00:00+00:00',
            includeUnchanged: true,
        ), false);

        $this->assertCount(1, $changes);
        $this->assertSame(ApplicationResultChangeType::Unchanged->value, $changes[0]['change_type']);
        $this->assertSame('APP-001|<null>', $changes[0]['entity_reference']);
    }

    public function test_comparator_rejects_ambiguous_keys(): void
    {
        $this->expectException(RuntimeException::class);
        iterator_to_array(app(ApplicationResultExportComparator::class)->compare(
            baseRows: [
                $this->row('APP-001', null, 'submitted'),
                $this->row('APP-001', null, 'eligible'),
            ],
            targetRows: [],
            entityType: 'application',
            keyFields: ['application_number', 'process_number'],
            comparedFields: ['submission_status_code'],
            beforeSource: 'batch-t1',
            afterSource: 'batch-t2',
            changedAt: '2026-08-01T12:00:00+00:00',
        ), false);
    }

    /** @return array<string, mixed> */
    private function row(
        string $applicationNumber,
        ?string $processNumber,
        string $status,
        ?string $candidateName = null,
    ): array {
        return [
            'application_number' => $applicationNumber,
            'process_number' => $processNumber,
            'submission_status_code' => $status,
            'candidate_name' => $candidateName,
        ];
    }
}
