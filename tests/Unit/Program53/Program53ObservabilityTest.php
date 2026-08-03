<?php

namespace Tests\Unit\Program53;

use App\Data\Program53\Program53OperationalContext;
use App\Services\Program53\Observability\Program53ContextRedactor;
use App\Services\Program53\Observability\StructuredLogProgram53MetricsRecorder;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

final class Program53ObservabilityTest extends TestCase
{
    public function test_redactor_removes_pii_paths_and_high_cardinality_labels(): void
    {
        $safe = app(Program53ContextRedactor::class)->redact([
            'event' => 'program53_metric',
            'operation_id' => 'operation-53',
            'email' => 'candidate@example.test',
            'path' => '/private/document.pdf',
            'exception_message' => 'payload with personal data',
            'labels' => [
                'status' => 'completed',
                'candidate_id' => 123,
                'filename' => 'private.pdf',
            ],
            'counts' => [
                'applications' => 10,
                'invalid key' => 20,
            ],
        ]);

        $this->assertSame('operation-53', $safe['operation_id']);
        $this->assertSame(['status' => 'completed'], $safe['labels']);
        $this->assertSame(['applications' => 10], $safe['counts']);
        $this->assertArrayNotHasKey('email', $safe);
        $this->assertArrayNotHasKey('path', $safe);
        $this->assertArrayNotHasKey('exception_message', $safe);
    }

    public function test_metrics_recorder_emits_minimized_structured_log(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                'program53.metric',
                $this->callback(static function (mixed $context): bool {
                    return is_array($context)
                        && $context['metric'] === 'snapshot_duration'
                        && $context['labels'] === ['status' => 'completed']
                        && ! array_key_exists('candidate_id', $context);
                }),
            );
        Log::swap($logger);
        $context = new Program53OperationalContext(
            operationId: 'operation-53',
            municipalityId: 1,
            contestId: 2,
            exportId: 3,
            stage: 'snapshot',
        );

        app(StructuredLogProgram53MetricsRecorder::class)->record(
            'snapshot_duration',
            125.5,
            $context,
            ['status' => 'completed', 'candidate_id' => 99],
        );
    }
}
