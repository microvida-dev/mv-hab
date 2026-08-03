<?php

namespace App\Services\Program53\Benchmark;

use App\Data\Program53\Program53BenchmarkConfiguration;
use App\Data\Reports\ApplicationResultExportPackageOptionsData;
use App\Data\Reports\ApplicationResultExportSnapshotData;
use App\Data\Reports\ApplicationResultExportSourceData;
use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportMode;
use App\Services\Reporting\Temporal\ApplicationResultExportPackageBuilder;
use App\Services\Reporting\Temporal\CanonicalNdjsonStore;
use App\Services\Support\CanonicalJsonHasher;
use Carbon\CarbonImmutable;
use Generator;
use PDO;
use PDOStatement;
use RuntimeException;

final class Program53BenchmarkRunner
{
    public function __construct(
        private readonly Program53BenchmarkEnvironment $environment,
        private readonly Program53ScaleScenarioBuilder $scenarios,
        private readonly CanonicalNdjsonStore $store,
        private readonly ApplicationResultExportPackageBuilder $packages,
        private readonly CanonicalJsonHasher $hasher,
    ) {}

    /** @return array<string, mixed> */
    public function run(Program53BenchmarkConfiguration $configuration): array
    {
        $this->environment->assertAllowed();
        $runId = $this->environment->runId($configuration);
        $this->environment->prepare($runId);
        $this->environment->activateStorage($runId);
        $metrics = new Program53BenchmarkMetrics;
        $metrics->start('total');

        try {
            $pdo = $this->scenarios->connect(
                $this->environment->databasePath($runId),
            );

            $metrics->start('schema');
            $this->scenarios->createSchema($pdo, $metrics);
            $metrics->stop('schema');

            $metrics->start('dataset');
            $this->scenarios->seed($pdo, $configuration, $metrics);
            $metrics->stop('dataset');

            $metrics->start('review');
            $this->runReview($pdo, $configuration, $metrics);
            $metrics->stop('review');

            $metrics->start('seal');
            $this->seal($pdo, $configuration, $metrics);
            $metrics->stop('seal');

            $metrics->start('publication');
            $this->publish($pdo, $configuration, $metrics);
            $metrics->stop('publication');

            $metrics->start('queue_wait');
            $queuedAt = hrtime(true);
            $queueId = $this->enqueue($pdo, $runId, $metrics);
            $this->reserve($pdo, $queueId, $metrics);
            $queueWaitMs = round((hrtime(true) - $queuedAt) / 1_000_000, 3);
            $metrics->stop('queue_wait');

            $metrics->start('snapshot');
            $snapshot = $this->snapshot(
                $pdo,
                $configuration,
                $runId,
                $metrics,
            );
            $metrics->stop('snapshot');

            $metrics->start('package');
            $generatedAt = CarbonImmutable::parse('2026-08-02 10:00:00', 'UTC');
            $package = $this->packages->build(
                $snapshot,
                new ApplicationResultExportPackageOptionsData(
                    exportPublicId: $this->deterministicUuid($configuration->seed),
                    formats: $configuration->formats,
                    datasets: ApplicationResultExportDataset::cases(),
                    generatedAt: $generatedAt,
                    expiresAt: $generatedAt->addDays(7),
                ),
                'program53-benchmark/'.$runId.'/package',
            );
            $metrics->stop('package');
            $this->completeQueue($pdo, $queueId, $metrics);

            $metrics->start('integrity');
            $integrity = $this->integrity(
                $pdo,
                $configuration,
                $snapshot,
                $package->packageSha256,
                $metrics,
            );
            $metrics->stop('integrity');
            $total = $metrics->stop('total');
            $peakMemory = memory_get_peak_usage(true);
            $memoryGuardrail = $this->memoryGuardrail();
            $warnings = [];
            if ($peakMemory > $memoryGuardrail) {
                $warnings[] = 'memory_guardrail_exceeded';
                $integrity['memory_within_guardrail'] = false;
            }

            $passed = ! in_array(false, $integrity, true);
            $counts = $this->counts($pdo, $metrics);
            $queryPlans = $this->queryPlans($pdo, $metrics);
            $artifactFiles = array_map(
                static fn ($file): array => [
                    'path' => $file->path,
                    'bytes' => $file->size,
                    'rows' => $file->rowCount,
                    'sha256' => $file->sha256,
                ],
                $package->files,
            );

            return [
                'timestamp' => now('UTC')->toIso8601String(),
                'commit' => trim((string) shell_exec('git rev-parse HEAD 2>/dev/null')),
                'runtime' => [
                    'php' => PHP_VERSION,
                    'laravel' => app()->version(),
                    'database' => 'SQLite '.($this->query($pdo, 'select sqlite_version()')->fetchColumn() ?: 'unknown'),
                    'cache_driver' => (string) config('cache.default'),
                    'queue_driver' => 'database',
                    'queue_workers' => $configuration->queueWorkers,
                    'filesystem' => 'isolated_local',
                    'memory_limit' => (string) ini_get('memory_limit'),
                    'cpu' => php_uname('m'),
                ],
                'dataset' => $configuration->toArray(),
                'counts' => $counts,
                'durations_seconds' => $metrics->durations(),
                'throughput' => [
                    'applications_per_second' => round(
                        $configuration->applications / max($total, 0.000001),
                        3,
                    ),
                    'export_rows_per_second' => round(
                        array_sum($snapshot->counts) / max(
                            (float) ($metrics->durations()['snapshot'] ?? 0.000001)
                            + (float) ($metrics->durations()['package'] ?? 0.000001),
                            0.000001,
                        ),
                        3,
                    ),
                ],
                'queries' => $metrics->queries(),
                'query_plans' => $queryPlans,
                'peak_memory_bytes' => $peakMemory,
                'memory_guardrail_bytes' => $memoryGuardrail,
                'queue' => [
                    'wait_ms' => $queueWaitMs,
                    'retries' => $metrics->retries(),
                    'deadlocks' => $metrics->deadlocks(),
                ],
                'artifacts' => [
                    'package_name' => $package->fileName,
                    'package_bytes' => $package->size,
                    'package_sha256' => $package->packageSha256,
                    'manifest_sha256' => $package->manifestSha256,
                    'files' => $artifactFiles,
                ],
                'integrity' => $integrity,
                'warnings' => [...$warnings, ...$package->warnings],
                'result' => $passed ? 'pass' : 'fail',
            ];
        } finally {
            $this->environment->restoreStorage();
        }
    }

    private function runReview(
        PDO $pdo,
        Program53BenchmarkConfiguration $configuration,
        Program53BenchmarkMetrics $metrics,
    ): void {
        $statement = $pdo->prepare(<<<'SQL'
UPDATE benchmark_applications
SET analyst_id = ((id - 1) % :analysts) + 1,
    outcome = CASE
        WHEN review_state = 'ready' THEN 'complete_pending_decision'
        WHEN correction_state IN ('open', 'responded', 'no_response') THEN 'correction_required'
        ELSE 'not_assessed'
    END,
    updated_at = '2026-08-02T09:15:00+00:00'
SQL);
        $statement->execute(['analysts' => $configuration->analysts]);
        $metrics->query();
    }

    private function seal(
        PDO $pdo,
        Program53BenchmarkConfiguration $configuration,
        Program53BenchmarkMetrics $metrics,
    ): void {
        $insert = $pdo->prepare(<<<'SQL'
INSERT OR IGNORE INTO benchmark_batches (
    contest_id, snapshot_hash, item_count, sealed_at
) VALUES (:contest_id, :snapshot_hash, :item_count, :sealed_at)
SQL);
        for ($contest = 1; $contest <= $configuration->contests; $contest++) {
            $select = $pdo->prepare(<<<'SQL'
SELECT id, source_hash, outcome
FROM benchmark_applications
WHERE contest_id = :contest_id
ORDER BY id
SQL);
            $select->execute(['contest_id' => $contest]);
            $metrics->query();
            $hash = hash_init('sha256');
            $count = 0;
            while (($row = $select->fetch()) !== false) {
                hash_update($hash, implode('|', [
                    (string) $row['id'],
                    (string) $row['source_hash'],
                    (string) $row['outcome'],
                ])."\n");
                $count++;
            }
            $insert->execute([
                'contest_id' => $contest,
                'snapshot_hash' => hash_final($hash),
                'item_count' => $count,
                'sealed_at' => '2026-08-02T09:30:00+00:00',
            ]);
            $metrics->query();
        }
    }

    private function publish(
        PDO $pdo,
        Program53BenchmarkConfiguration $configuration,
        Program53BenchmarkMetrics $metrics,
    ): void {
        $pdo->beginTransaction();
        try {
            for ($contest = 1; $contest <= $configuration->contests; $contest++) {
                $batch = $this->query(
                    $pdo,
                    'SELECT id, snapshot_hash FROM benchmark_batches WHERE contest_id = '.(int) $contest,
                )->fetch();
                $metrics->query();
                if (! is_array($batch)) {
                    throw new RuntimeException('O benchmark não conseguiu resolver o lote selado.');
                }
                $publicationHash = hash('sha256', 'publication:'.$batch['snapshot_hash']);
                $publication = $pdo->prepare(<<<'SQL'
INSERT OR IGNORE INTO benchmark_publications (
    batch_id, publication_hash, published_at
) VALUES (:batch_id, :publication_hash, :published_at)
SQL);
                $publication->execute([
                    'batch_id' => $batch['id'],
                    'publication_hash' => $publicationHash,
                    'published_at' => '2026-08-02T09:45:00+00:00',
                ]);
                $metrics->query();
                $publicationId = $this->query(
                    $pdo,
                    'SELECT id FROM benchmark_publications WHERE batch_id = '.(int) $batch['id'],
                )->fetchColumn();
                $metrics->query();
                if (! is_int($publicationId) && ! is_string($publicationId)) {
                    throw new RuntimeException('A publicação sintética não foi criada.');
                }
                $pdo->exec(<<<SQL
INSERT OR IGNORE INTO benchmark_results (
    publication_id, application_id, result_hash
)
SELECT {$publicationId}, id, source_hash
FROM benchmark_applications
WHERE contest_id = {$contest}
SQL);
                $metrics->query();
            }

            $pdo->exec(<<<'SQL'
INSERT OR IGNORE INTO benchmark_notifications (
    application_id, result_id, status, attempts
)
SELECT application_id, id, 'delivered', 1
FROM benchmark_results
SQL);
            $metrics->query();
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();

            throw $exception;
        }
    }

    private function enqueue(PDO $pdo, string $runId, Program53BenchmarkMetrics $metrics): int
    {
        $statement = $pdo->prepare(<<<'SQL'
INSERT INTO benchmark_queue (
    queue, payload, status, available_at
) VALUES ('reports', :payload, 'pending', :available_at)
SQL);
        $statement->execute([
            'payload' => json_encode(['run_id_hash' => hash('sha256', $runId)], JSON_THROW_ON_ERROR),
            'available_at' => '2026-08-02T09:50:00+00:00',
        ]);
        $metrics->query();

        return (int) $pdo->lastInsertId();
    }

    private function reserve(PDO $pdo, int $queueId, Program53BenchmarkMetrics $metrics): void
    {
        $statement = $pdo->prepare(<<<'SQL'
UPDATE benchmark_queue
SET status = 'processing', attempts = attempts + 1,
    reserved_at = '2026-08-02T09:50:01+00:00'
WHERE id = :id AND status = 'pending'
SQL);
        $statement->execute(['id' => $queueId]);
        $metrics->query();
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('O worker assíncrono não reservou o job de exportação.');
        }
    }

    private function completeQueue(PDO $pdo, int $queueId, Program53BenchmarkMetrics $metrics): void
    {
        $statement = $pdo->prepare(<<<'SQL'
UPDATE benchmark_queue
SET status = 'completed', completed_at = '2026-08-02T10:05:00+00:00'
WHERE id = :id AND status = 'processing'
SQL);
        $statement->execute(['id' => $queueId]);
        $metrics->query();
    }

    private function snapshot(
        PDO $pdo,
        Program53BenchmarkConfiguration $configuration,
        string $runId,
        Program53BenchmarkMetrics $metrics,
    ): ApplicationResultExportSnapshotData {
        $directory = 'program53-benchmark/'.$runId.'/source';
        $this->store->createDirectory($directory);
        $fingerprint = $this->hasher->hash([
            'schema_version' => '1.0',
            'scenario' => $configuration->scenario,
            'seed' => $configuration->seed,
            'applications' => $configuration->applications,
            'municipalities' => $configuration->municipalities,
            'contests' => $configuration->contests,
        ]);
        $paths = [
            'applications' => $directory.'/applications.ndjson',
            'documents' => $directory.'/documents.ndjson',
            'findings' => $directory.'/findings.ndjson',
            'changes' => $directory.'/changes.ndjson',
        ];
        $counts = [
            'applications' => $this->store->write(
                $paths['applications'],
                $this->applicationRows($pdo, $fingerprint, $metrics),
            ),
            'documents' => $this->store->write(
                $paths['documents'],
                $this->documentRows($pdo, $metrics),
            ),
            'findings' => $this->store->write(
                $paths['findings'],
                $this->findingRows($pdo, $metrics),
            ),
            'changes' => $this->store->write(
                $paths['changes'],
                $this->changeRows($pdo, $metrics),
            ),
        ];
        $checksums = [];
        foreach ($paths as $dataset => $path) {
            $checksums[$dataset] = $this->store->checksum($path);
        }

        return new ApplicationResultExportSnapshotData(
            source: new ApplicationResultExportSourceData(
                mode: ApplicationResultExportMode::DeltaBetweenBatches,
                municipalityId: 0,
                contestId: 0,
                municipalityCode: 'SYNTHETIC',
                contestCode: 'PROGRAM53-BENCHMARK',
                snapshotAt: CarbonImmutable::parse('2026-08-02 09:45:00', 'UTC'),
                official: false,
                sourceType: 'synthetic_benchmark_delta',
                sourceReferences: [
                    'base_batch' => ['public_id' => 'synthetic-base'],
                    'target_batch' => ['public_id' => 'synthetic-target'],
                ],
            ),
            datasetPaths: $paths,
            counts: $counts,
            checksums: $checksums,
            sourceFingerprint: $fingerprint,
            warnings: ['synthetic_benchmark_data'],
        );
    }

    /** @return Generator<int, array<string, mixed>> */
    private function applicationRows(
        PDO $pdo,
        string $fingerprint,
        Program53BenchmarkMetrics $metrics,
    ): Generator {
        $statement = $this->query($pdo, <<<'SQL'
SELECT id, municipality_id, contest_id, status, document_state,
       correction_state, review_state, outcome, updated_at
FROM benchmark_applications
ORDER BY id
SQL);
        $metrics->query();
        while (($row = $statement->fetch()) !== false) {
            $documents = ((int) $row['id'] % 2) + 2;
            $valid = in_array($row['document_state'], ['valid', 'replaced'], true)
                ? $documents
                : 0;
            yield [
                'municipality_code' => 'MUN-'.str_pad((string) $row['municipality_id'], 2, '0', STR_PAD_LEFT),
                'contest_code' => 'CONTEST-'.str_pad((string) $row['contest_id'], 2, '0', STR_PAD_LEFT),
                'contest_public_id' => null,
                'phase_code' => 'revalidation',
                'batch_public_id' => 'synthetic-target',
                'batch_cycle' => 'revalidation',
                'batch_sequence' => 2,
                'snapshot_at' => '2026-08-02T09:45:00+00:00',
                'published_at' => '2026-08-02T09:45:00+00:00',
                'application_number' => 'SYN-'.str_pad((string) $row['id'], 8, '0', STR_PAD_LEFT),
                'process_number' => 'PROC-SYN-'.str_pad((string) $row['id'], 8, '0', STR_PAD_LEFT),
                'submission_status_code' => 'submitted',
                'submission_status_label' => 'Submetida',
                'review_status_code' => 'completed',
                'review_status_label' => 'Concluída',
                'review_result_code' => $row['outcome'],
                'review_result_label' => $this->outcomeLabel((string) $row['outcome']),
                'documents_required' => $documents,
                'documents_valid' => $valid,
                'documents_missing' => $row['document_state'] === 'missing' ? $documents : 0,
                'documents_invalid' => $row['document_state'] === 'rejected' ? $documents : 0,
                'correction_required' => $row['correction_state'] !== 'not_required',
                'correction_deadline' => $row['correction_state'] !== 'not_required'
                    ? '2026-08-09T23:59:59+00:00'
                    : null,
                'correction_submitted_at' => $row['correction_state'] === 'responded'
                    ? '2026-08-06T14:00:00+00:00'
                    : null,
                'revalidation_result_code' => $row['correction_state'] === 'responded'
                    ? 'accepted'
                    : null,
                'eligibility_status_code' => null,
                'eligibility_status_label' => null,
                'score_status_code' => null,
                'score_status_label' => null,
                'final_administrative_status_code' => null,
                'final_administrative_status_label' => null,
                'last_changed_at' => $row['updated_at'],
                'source_fingerprint' => $fingerprint,
            ];
        }
    }

    /** @return Generator<int, array<string, mixed>> */
    private function documentRows(PDO $pdo, Program53BenchmarkMetrics $metrics): Generator
    {
        $statement = $this->query($pdo, <<<'SQL'
SELECT id, document_state
FROM benchmark_applications
ORDER BY id
SQL);
        $metrics->query();
        while (($row = $statement->fetch()) !== false) {
            $count = ((int) $row['id'] % 2) + 2;
            for ($document = 1; $document <= $count; $document++) {
                yield [
                    'application_number' => 'SYN-'.str_pad((string) $row['id'], 8, '0', STR_PAD_LEFT),
                    'process_number' => 'PROC-SYN-'.str_pad((string) $row['id'], 8, '0', STR_PAD_LEFT),
                    'required_document_code' => 'SYNTHETIC-'.$document,
                    'document_type_code' => 'SYNTHETIC-'.$document,
                    'target_type' => 'application',
                    'target_reference' => 'synthetic:'.$row['id'],
                    'requirement_instance' => $document,
                    'required_submissions' => $count,
                    'reference_period' => null,
                    'document_status_code' => match ($row['document_state']) {
                        'valid', 'replaced' => 'validated',
                        'missing' => 'missing',
                        default => 'rejected',
                    },
                    'version_number' => $row['document_state'] === 'replaced' ? 2 : 1,
                    'submitted_at' => '2026-08-01T10:00:00+00:00',
                    'validated_at' => in_array($row['document_state'], ['valid', 'replaced'], true)
                        ? '2026-08-02T08:00:00+00:00'
                        : null,
                    'source_sha256' => hash('sha256', 'synthetic:'.$row['id'].':'.$document),
                    'carried_forward' => false,
                    'source_batch_public_id' => 'synthetic-target',
                ];
            }
        }
    }

    /** @return Generator<int, array<string, mixed>> */
    private function findingRows(PDO $pdo, Program53BenchmarkMetrics $metrics): Generator
    {
        $statement = $this->query($pdo, <<<'SQL'
SELECT id, correction_state
FROM benchmark_applications
WHERE correction_state <> 'not_required'
ORDER BY id
SQL);
        $metrics->query();
        while (($row = $statement->fetch()) !== false) {
            yield [
                'application_number' => 'SYN-'.str_pad((string) $row['id'], 8, '0', STR_PAD_LEFT),
                'finding_code' => 'synthetic-finding-'.$row['id'],
                'requirement_code' => 'SYNTHETIC-1',
                'finding_status_code' => $row['correction_state'] === 'responded' ? 'resolved' : 'open',
                'finding_status_label' => $row['correction_state'] === 'responded' ? 'Resolvido' : 'Aberto',
                'decision_code' => $row['correction_state'] === 'responded' ? 'accepted' : null,
                'carried_forward' => false,
                'source_batch_public_id' => 'synthetic-target',
                'decided_at' => $row['correction_state'] === 'responded'
                    ? '2026-08-02T09:00:00+00:00'
                    : null,
                'resolved_at' => $row['correction_state'] === 'responded'
                    ? '2026-08-02T09:00:00+00:00'
                    : null,
            ];
        }
    }

    /** @return Generator<int, array<string, mixed>> */
    private function changeRows(PDO $pdo, Program53BenchmarkMetrics $metrics): Generator
    {
        $statement = $this->query($pdo, <<<'SQL'
SELECT id
FROM benchmark_applications
WHERE id % 5 = 0
ORDER BY id
SQL);
        $metrics->query();
        while (($row = $statement->fetch()) !== false) {
            $applicationNumber = 'SYN-'.str_pad((string) $row['id'], 8, '0', STR_PAD_LEFT);
            yield [
                'entity_type' => 'document',
                'entity_reference' => $applicationNumber.'|SYNTHETIC-1',
                'application_number' => $applicationNumber,
                'change_type' => 'changed',
                'field_code' => 'document_status_code',
                'before_value' => 'submitted',
                'after_value' => 'validated',
                'before_source' => 'synthetic-base',
                'after_source' => 'synthetic-target',
                'changed_at' => '2026-08-02T09:45:00+00:00',
                'sensitive_value_redacted' => false,
            ];
        }
    }

    /**
     * @return array<string, bool>
     */
    private function integrity(
        PDO $pdo,
        Program53BenchmarkConfiguration $configuration,
        ApplicationResultExportSnapshotData $snapshot,
        string $packageSha256,
        Program53BenchmarkMetrics $metrics,
    ): array {
        $counts = $this->counts($pdo, $metrics);
        $crossScope = (int) $this->query($pdo, <<<SQL
SELECT COUNT(*)
FROM benchmark_applications
WHERE municipality_id <> (((contest_id - 1) % {$configuration->municipalities}) + 1)
SQL)->fetchColumn();
        $metrics->query();
        $pendingQueue = (int) $this->query(
            $pdo,
            "SELECT COUNT(*) FROM benchmark_queue WHERE status <> 'completed'",
        )->fetchColumn();
        $metrics->query();
        $queryLimit = (int) ceil(
            $configuration->applications
            / max(1, (int) config('program53.benchmark.chunk_size', 50)),
        ) + (12 * $configuration->contests) + 50;

        return [
            'applications_preserved' => $counts['applications'] === $configuration->applications,
            'one_batch_per_contest' => $counts['batches'] === $configuration->contests,
            'one_publication_per_contest' => $counts['publications'] === $configuration->contests,
            'one_result_per_application' => $counts['results'] === $configuration->applications,
            'one_notification_per_application' => $counts['notifications'] === $configuration->applications,
            'no_cross_municipality_rows' => $crossScope === 0,
            'queue_completed' => $pendingQueue === 0,
            'snapshot_application_count' => ($snapshot->counts['applications'] ?? 0)
                === $configuration->applications,
            'package_hash_valid' => preg_match('/^[a-f0-9]{64}$/', $packageSha256) === 1,
            'queries_bounded_by_chunks' => $metrics->queries() <= $queryLimit,
            'memory_within_guardrail' => true,
        ];
    }

    /** @return array<string, int> */
    private function counts(PDO $pdo, Program53BenchmarkMetrics $metrics): array
    {
        $tables = [
            'applications' => 'benchmark_applications',
            'batches' => 'benchmark_batches',
            'publications' => 'benchmark_publications',
            'results' => 'benchmark_results',
            'notifications' => 'benchmark_notifications',
            'queue_jobs' => 'benchmark_queue',
        ];
        $counts = [];
        foreach ($tables as $key => $table) {
            $counts[$key] = (int) $this->query(
                $pdo,
                "SELECT COUNT(*) FROM {$table}",
            )->fetchColumn();
            $metrics->query();
        }

        return $counts;
    }

    /** @return array<string, list<string>> */
    private function queryPlans(PDO $pdo, Program53BenchmarkMetrics $metrics): array
    {
        $queries = [
            'workspace' => <<<'SQL'
SELECT id, status, document_state, correction_state
FROM benchmark_applications
WHERE municipality_id = 1 AND contest_id = 1 AND id > 0
ORDER BY id
LIMIT 250
SQL,
            'analyst_queue' => <<<'SQL'
SELECT id, outcome
FROM benchmark_applications
WHERE contest_id = 1 AND analyst_id = 1 AND review_state = 'ready'
ORDER BY id
LIMIT 250
SQL,
            'publication_results' => <<<'SQL'
SELECT id, application_id
FROM benchmark_results
WHERE publication_id = 1
ORDER BY id
LIMIT 250
SQL,
            'queue_ready' => <<<'SQL'
SELECT id
FROM benchmark_queue
WHERE queue = 'reports' AND status = 'pending'
ORDER BY available_at, id
LIMIT 1
SQL,
        ];
        $plans = [];
        foreach ($queries as $name => $query) {
            $statement = $this->query($pdo, 'EXPLAIN QUERY PLAN '.$query);
            $metrics->query();
            $steps = [];
            while (($row = $statement->fetch()) !== false) {
                $steps[] = (string) ($row['detail'] ?? 'unknown');
            }
            $plans[$name] = $steps;
        }

        return $plans;
    }

    private function query(PDO $pdo, string $sql): PDOStatement
    {
        $statement = $pdo->query($sql);
        if (! $statement instanceof PDOStatement) {
            throw new RuntimeException('A base isolada recusou uma query do benchmark.');
        }

        return $statement;
    }

    private function outcomeLabel(string $outcome): string
    {
        return match ($outcome) {
            'complete_pending_decision' => 'Completa, pendente de decisão',
            'correction_required' => 'Aperfeiçoamento necessário',
            default => 'Não avaliada',
        };
    }

    private function deterministicUuid(int $seed): string
    {
        $hex = substr(hash('sha256', 'program53-benchmark:'.$seed), 0, 32);

        return sprintf(
            '%s-%s-7%s-8%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 13, 3),
            substr($hex, 17, 3),
            substr($hex, 20, 12),
        );
    }

    private function memoryGuardrail(): int
    {
        $configured = (int) config(
            'program53.benchmark.memory_guardrail_bytes',
            512 * 1024 * 1024,
        );
        $ini = $this->bytes((string) ini_get('memory_limit'));

        return $ini > 0 ? min($configured, $ini) : $configured;
    }

    private function bytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return 0;
        }
        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
