<?php

namespace App\Services\Program53\Benchmark;

use App\Data\Program53\Program53BenchmarkConfiguration;
use PDO;
use RuntimeException;

final class Program53ScaleScenarioBuilder
{
    public function connect(string $databasePath): PDO
    {
        if (file_exists($databasePath) && ! unlink($databasePath)) {
            throw new RuntimeException('Não foi possível reiniciar a base isolada do benchmark.');
        }

        $pdo = new PDO('sqlite:'.$databasePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA synchronous = NORMAL');
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');

        return $pdo;
    }

    public function createSchema(PDO $pdo, Program53BenchmarkMetrics $metrics): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE benchmark_applications (
    id INTEGER PRIMARY KEY,
    municipality_id INTEGER NOT NULL,
    contest_id INTEGER NOT NULL,
    analyst_id INTEGER,
    status TEXT NOT NULL,
    document_state TEXT NOT NULL,
    correction_state TEXT NOT NULL,
    review_state TEXT NOT NULL,
    outcome TEXT,
    source_hash TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE INDEX benchmark_applications_scope_idx
    ON benchmark_applications (municipality_id, contest_id, id);
CREATE INDEX benchmark_applications_work_idx
    ON benchmark_applications (contest_id, analyst_id, review_state, id);

CREATE TABLE benchmark_batches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contest_id INTEGER NOT NULL UNIQUE,
    snapshot_hash TEXT NOT NULL UNIQUE,
    item_count INTEGER NOT NULL,
    sealed_at TEXT NOT NULL
);

CREATE TABLE benchmark_publications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_id INTEGER NOT NULL UNIQUE,
    publication_hash TEXT NOT NULL UNIQUE,
    published_at TEXT NOT NULL,
    FOREIGN KEY (batch_id) REFERENCES benchmark_batches (id)
);

CREATE TABLE benchmark_results (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    publication_id INTEGER NOT NULL,
    application_id INTEGER NOT NULL UNIQUE,
    result_hash TEXT NOT NULL UNIQUE,
    FOREIGN KEY (publication_id) REFERENCES benchmark_publications (id),
    FOREIGN KEY (application_id) REFERENCES benchmark_applications (id)
);
CREATE INDEX benchmark_results_publication_idx
    ON benchmark_results (publication_id, id);

CREATE TABLE benchmark_notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    application_id INTEGER NOT NULL UNIQUE,
    result_id INTEGER NOT NULL UNIQUE,
    status TEXT NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (application_id) REFERENCES benchmark_applications (id),
    FOREIGN KEY (result_id) REFERENCES benchmark_results (id)
);

CREATE TABLE benchmark_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    status TEXT NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    available_at TEXT NOT NULL,
    reserved_at TEXT,
    completed_at TEXT
);
CREATE INDEX benchmark_queue_ready_idx
    ON benchmark_queue (queue, status, available_at, id);
SQL);
        $metrics->query(8);
    }

    public function seed(
        PDO $pdo,
        Program53BenchmarkConfiguration $configuration,
        Program53BenchmarkMetrics $metrics,
    ): void {
        $chunkSize = (int) config('program53.benchmark.chunk_size', 500);
        $rows = [];

        for ($id = 1; $id <= $configuration->applications; $id++) {
            $contestId = (($id - 1) % $configuration->contests) + 1;
            $municipalityId = (($contestId - 1) % $configuration->municipalities) + 1;
            $bucket = $this->bucket($configuration->seed, $id);
            $documentState = match (true) {
                $bucket < 65 => 'valid',
                $bucket < 80 => 'missing',
                $bucket < 90 => 'rejected',
                default => 'replaced',
            };
            $correctionState = match (true) {
                $bucket >= 85 && $bucket < 90 => 'open',
                $bucket >= 90 && $bucket < 97 => 'responded',
                $bucket >= 97 => 'no_response',
                default => 'not_required',
            };
            $ready = $documentState === 'valid' || $documentState === 'replaced';
            $rows[] = [
                $id,
                $municipalityId,
                $contestId,
                $bucket < 55 ? 'submitted_complete' : 'submitted_incomplete',
                $documentState,
                $correctionState,
                $ready ? 'ready' : 'blocked',
                hash('sha256', implode(':', [
                    $configuration->seed,
                    $municipalityId,
                    $contestId,
                    $id,
                    $documentState,
                    $correctionState,
                ])),
                '2026-08-02T09:00:00+00:00',
            ];

            if (count($rows) === $chunkSize || $id === $configuration->applications) {
                $this->insertChunk($pdo, $rows);
                $metrics->query();
                $rows = [];
            }
        }
    }

    /** @param list<list<int|string>> $rows */
    private function insertChunk(PDO $pdo, array $rows): void
    {
        $placeholders = implode(',', array_fill(
            0,
            count($rows),
            '(?, ?, ?, NULL, ?, ?, ?, ?, NULL, ?, ?)',
        ));
        $values = [];
        foreach ($rows as $row) {
            array_push($values, ...$row);
        }

        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(<<<SQL
INSERT INTO benchmark_applications (
    id, municipality_id, contest_id, analyst_id, status,
    document_state, correction_state, review_state, outcome,
    source_hash, updated_at
) VALUES {$placeholders}
SQL);
            $statement->execute($values);
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();

            throw $exception;
        }
    }

    private function bucket(int $seed, int $id): int
    {
        return (int) sprintf('%u', crc32($seed.':'.$id)) % 100;
    }
}
