<?php

namespace App\Services\Program53\Operations;

use App\Data\Program53\Program53HealthFinding;
use App\Enums\Program53HealthSeverity;
use App\Enums\ReportExportStatus;
use App\Models\ReportExport;
use App\Services\Access\Program53AccessAuditService;
use App\Services\Reporting\Temporal\ApplicationResultExportSchemaValidator;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

/**
 * Diagnóstico técnico sem reparações automáticas nem escrita persistente na BD.
 */
final class Program53OperationalHealthService
{
    /** @var list<Program53HealthFinding> */
    private array $findings = [];

    public function __construct(
        private readonly Program53AccessAuditService $accessAudit,
    ) {}

    /**
     * @return array{
     *   schema_version: string,
     *   generated_at: string,
     *   environment: string,
     *   summary: array{total: int, info: int, warning: int, critical: int},
     *   findings: list<array{code: string, severity: string, message: string, context: array<string, bool|int|string|null>}>
     * }
     */
    public function inspect(): array
    {
        $this->findings = [];

        $this->inspectDatabase();
        $this->inspectAccessManifest();
        $this->inspectSchemas();
        $this->inspectQueue();
        $this->inspectCacheLocks();
        $this->inspectRateLimits();
        $this->inspectStorage();
        $this->inspectExports();
        $this->inspectScheduler();
        $this->inspectConfiguration();

        usort(
            $this->findings,
            static fn (Program53HealthFinding $left, Program53HealthFinding $right): int => strcmp(
                $left->code,
                $right->code,
            ),
        );

        $serialized = array_map(
            static fn (Program53HealthFinding $finding): array => $finding->toArray(),
            $this->findings,
        );

        return [
            'schema_version' => '1.0',
            'generated_at' => now()->utc()->toIso8601String(),
            'environment' => (string) app()->environment(),
            'summary' => [
                'total' => count($serialized),
                'info' => $this->count(Program53HealthSeverity::Info),
                'warning' => $this->count(Program53HealthSeverity::Warning),
                'critical' => $this->count(Program53HealthSeverity::Critical),
            ],
            'findings' => $serialized,
        ];
    }

    private function inspectDatabase(): void
    {
        try {
            DB::connection()->getPdo();
            $this->add('database.connection', Program53HealthSeverity::Info, 'Ligação à base de dados disponível.');
        } catch (Throwable) {
            $this->add('database.connection', Program53HealthSeverity::Critical, 'Ligação à base de dados indisponível.');

            return;
        }

        $requiredTables = [
            'application_review_batches',
            'application_review_publications',
            'correction_requests',
            'report_exports',
            'report_runs',
        ];
        $missing = array_values(array_filter(
            $requiredTables,
            static fn (string $table): bool => ! Schema::hasTable($table),
        ));
        $this->add(
            'database.program53_tables',
            $missing === [] ? Program53HealthSeverity::Info : Program53HealthSeverity::Critical,
            $missing === []
                ? 'Tabelas nucleares do Programa 53 disponíveis.'
                : 'Existem tabelas nucleares do Programa 53 em falta.',
            ['missing_count' => count($missing)],
        );
        if ($missing !== [] || ! Schema::hasTable('migrations')) {
            return;
        }

        $migration = '2026_08_01_000054_extend_report_exports_for_temporal_application_results';
        $applied = DB::table('migrations')->where('migration', $migration)->exists();
        $this->add(
            'database.temporal_export_migration',
            $applied ? Program53HealthSeverity::Info : Program53HealthSeverity::Critical,
            $applied
                ? 'Migration temporal de exportações aplicada.'
                : 'Migration temporal de exportações não aplicada.',
        );

        try {
            $names = array_values(array_filter(array_map(
                static fn (array $index): ?string => is_string($index['name'] ?? null)
                    ? $index['name']
                    : null,
                Schema::getIndexes('report_exports'),
            )));
            $requiredIndexes = [
                're_municipality_profile_created_idx',
                're_contest_idx',
                're_source_fingerprint_idx',
                're_idempotency_key_unique',
            ];
            $missingIndexes = array_diff($requiredIndexes, $names);
            $this->add(
                'database.temporal_export_indexes',
                $missingIndexes === [] ? Program53HealthSeverity::Info : Program53HealthSeverity::Critical,
                $missingIndexes === []
                    ? 'Índices críticos das exportações temporais disponíveis.'
                    : 'Existem índices críticos das exportações temporais em falta.',
                ['missing_count' => count($missingIndexes)],
            );
        } catch (Throwable) {
            $this->add('database.temporal_export_indexes', Program53HealthSeverity::Warning, 'Não foi possível inspecionar os índices críticos.');
        }
    }

    private function inspectAccessManifest(): void
    {
        try {
            $result = $this->accessAudit->audit();
            $drift = $result['summary']['drift'];
            $this->add(
                'access.program53_manifest',
                $drift ? Program53HealthSeverity::Critical : Program53HealthSeverity::Info,
                $drift
                    ? 'A matriz de acesso do Programa 53 apresenta drift.'
                    : 'A matriz de acesso do Programa 53 não apresenta drift.',
                ['failed_checks' => $result['summary']['failed']],
            );
        } catch (Throwable) {
            $this->add('access.program53_manifest', Program53HealthSeverity::Critical, 'A auditoria da matriz de acesso não pôde ser concluída.');
        }
    }

    private function inspectSchemas(): void
    {
        $jsonValid = false;
        try {
            $contents = file_get_contents(base_path(ApplicationResultExportSchemaValidator::JSON_SCHEMA));
            if (is_string($contents)) {
                json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
                $jsonValid = true;
            }
        } catch (JsonException) {
            $jsonValid = false;
        }
        $this->add(
            'schema.json',
            $jsonValid ? Program53HealthSeverity::Info : Program53HealthSeverity::Critical,
            $jsonValid ? 'JSON Schema versionado válido.' : 'JSON Schema versionado ausente ou inválido.',
        );

        $xsd = file_get_contents(base_path(ApplicationResultExportSchemaValidator::XML_SCHEMA));
        $xmlValid = is_string($xsd)
            && function_exists('simplexml_load_string')
            && @simplexml_load_string($xsd) !== false;
        $this->add(
            'schema.xsd',
            $xmlValid ? Program53HealthSeverity::Info : Program53HealthSeverity::Critical,
            $xmlValid ? 'XSD versionado válido.' : 'XSD versionado ausente ou inválido.',
        );
    }

    private function inspectQueue(): void
    {
        $connection = (string) config('queue.default', 'sync');
        $local = app()->environment(['local', 'testing', 'benchmark']);
        $supported = in_array($connection, ['database', 'redis'], true);
        $severity = $supported
            ? Program53HealthSeverity::Info
            : ($connection === 'sync' && $local
                ? Program53HealthSeverity::Warning
                : Program53HealthSeverity::Critical);
        $this->add(
            'queue.connection',
            $severity,
            $supported
                ? 'Ligação assíncrona de filas configurada.'
                : 'A ligação de filas não valida execução assíncrona no ambiente atual.',
            ['connection' => $connection],
        );

        $retryAfter = (int) config("queue.connections.{$connection}.retry_after", 0);
        $timeout = (int) config('program53.queues.reports.timeout', 1800);
        $this->add(
            'queue.reports_timeout',
            $supported && $retryAfter > $timeout
                ? Program53HealthSeverity::Info
                : ($connection === 'sync' && $local
                    ? Program53HealthSeverity::Warning
                    : Program53HealthSeverity::Critical),
            $retryAfter > $timeout
                ? 'retry_after é superior ao timeout do job de reporting.'
                : 'A relação retry_after/timeout exige correção no ambiente assíncrono.',
            ['retry_after' => $retryAfter, 'timeout' => $timeout],
        );

        if (Schema::hasTable('jobs')) {
            $pending = DB::table('jobs')->where('queue', 'reports')->count();
            $heartbeat = Cache::get('program53:worker:reports:heartbeat');
            $missingWorker = $pending > 0 && ! is_string($heartbeat);
            $this->add(
                'queue.reports_worker',
                $missingWorker
                    ? ($local ? Program53HealthSeverity::Warning : Program53HealthSeverity::Critical)
                    : Program53HealthSeverity::Info,
                $missingWorker
                    ? 'Existem jobs de reporting sem heartbeat de worker observável.'
                    : 'Não existem indícios de backlog sem worker de reporting.',
                ['pending_jobs' => $pending],
            );
        }

        if (Schema::hasTable('failed_jobs')) {
            $failed = DB::table('failed_jobs')->count();
            $oldThreshold = now()->subSeconds((int) config(
                'program53.exports.failed_job_warning_after_seconds',
                3600,
            ));
            $old = DB::table('failed_jobs')->where('failed_at', '<=', $oldThreshold)->count();
            $this->add(
                'queue.failed_jobs',
                $old > 0
                    ? Program53HealthSeverity::Critical
                    : ($failed > 0 ? Program53HealthSeverity::Warning : Program53HealthSeverity::Info),
                $failed > 0 ? 'Existem jobs falhados por reconciliar.' : 'Não existem jobs falhados.',
                ['failed_jobs' => $failed, 'old_failed_jobs' => $old],
            );
        }
    }

    private function inspectCacheLocks(): void
    {
        $key = 'program53:health:lock:'.Str::uuid();
        try {
            $lock = Cache::lock($key, 10);
            $acquired = $lock->get();
            if ($acquired) {
                $lock->release();
            }
            $this->add(
                'cache.atomic_locks',
                $acquired ? Program53HealthSeverity::Info : Program53HealthSeverity::Critical,
                $acquired ? 'Atomic locks disponíveis.' : 'Atomic lock não pôde ser adquirido.',
            );
        } catch (Throwable) {
            $this->add('cache.atomic_locks', Program53HealthSeverity::Critical, 'Cache de atomic locks indisponível.');
        } finally {
            Cache::forget($key);
        }
    }

    private function inspectRateLimits(): void
    {
        $names = [
            'program53.export-preview',
            'program53.export-request',
            'program53.export-download',
            'program53.batch-seal',
            'program53.batch-publish',
            'program53.revalidation-seal',
        ];
        $missing = count(array_filter(
            $names,
            static fn (string $name): bool => RateLimiter::limiter($name) === null,
        ));
        $this->add(
            'security.rate_limits',
            $missing === 0 ? Program53HealthSeverity::Info : Program53HealthSeverity::Critical,
            $missing === 0
                ? 'Rate limiters do Programa 53 registados.'
                : 'Existem rate limiters do Programa 53 não registados.',
            ['missing_count' => $missing],
        );
    }

    private function inspectStorage(): void
    {
        $disk = Storage::disk('local');
        $directory = 'program53-health/'.Str::uuid();
        $source = $directory.'/probe.tmp';
        $target = $directory.'/probe.ok';
        $available = false;
        try {
            $available = $disk->put($source, 'program53-health')
                && $disk->get($source) === 'program53-health'
                && $disk->move($source, $target)
                && $disk->delete($target);
        } catch (Throwable) {
            $available = false;
        } finally {
            $disk->deleteDirectory($directory);
        }
        $this->add(
            'storage.private_operations',
            $available ? Program53HealthSeverity::Info : Program53HealthSeverity::Critical,
            $available
                ? 'Storage privado suporta escrita, leitura, move e remoção controlados.'
                : 'Storage privado não concluiu o probe operacional.',
        );

        $free = disk_free_space(storage_path());
        $minimum = (int) config('program53.benchmark.minimum_free_disk_bytes', 536870912);
        $this->add(
            'storage.free_space',
            $free === false || $free >= $minimum
                ? Program53HealthSeverity::Info
                : Program53HealthSeverity::Critical,
            $free === false
                ? 'Espaço livre deve ser confirmado pelo ambiente alvo.'
                : 'Espaço livre do storage inspecionado.',
            [
                'free_bytes' => $free === false ? null : (int) $free,
                'minimum_bytes' => $minimum,
            ],
        );
    }

    private function inspectExports(): void
    {
        if (! Schema::hasTable('report_exports')) {
            return;
        }

        $staleAt = now()->subSeconds((int) config(
            'program53.exports.stale_after_seconds',
            2100,
        ));
        $stale = ReportExport::query()
            ->where('export_profile', TemporalApplicationResultExportService::PROFILE)
            ->where('status', ReportExportStatus::Processing->value)
            ->where('updated_at', '<=', $staleAt)
            ->count();
        $this->add(
            'exports.stale',
            $stale > 0 ? Program53HealthSeverity::Warning : Program53HealthSeverity::Info,
            $stale > 0 ? 'Existem exportações em processamento stale.' : 'Não existem exportações stale.',
            ['count' => $stale],
        );

        $missingPackages = 0;
        $invalidPackageHashes = 0;
        $invalidMetadataHashes = 0;
        $disk = Storage::disk('local');
        ReportExport::query()
            ->where('export_profile', TemporalApplicationResultExportService::PROFILE)
            ->where('status', ReportExportStatus::Completed->value)
            ->select([
                'id',
                'file_path',
                'source_fingerprint',
                'manifest_sha256',
                'package_sha256',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($exports) use (
                &$missingPackages,
                &$invalidPackageHashes,
                &$invalidMetadataHashes,
                $disk,
            ): void {
                foreach ($exports as $export) {
                    $path = ltrim((string) $export->file_path, '/');
                    if (
                        ! $this->validSha256($export->source_fingerprint)
                        || ! $this->validSha256($export->manifest_sha256)
                    ) {
                        $invalidMetadataHashes++;
                    }
                    if (
                        $path === ''
                        || str_contains($path, '..')
                        || ! $disk->exists($path)
                    ) {
                        $missingPackages++;

                        continue;
                    }

                    $expected = is_string($export->package_sha256)
                        ? $export->package_sha256
                        : '';
                    $actual = hash_file('sha256', $disk->path($path));
                    if (
                        ! $this->validSha256($expected)
                        || ! is_string($actual)
                        || ! hash_equals($expected, $actual)
                    ) {
                        $invalidPackageHashes++;
                    }
                }
            });
        $packagesValid = $missingPackages === 0
            && $invalidPackageHashes === 0
            && $invalidMetadataHashes === 0;
        $this->add(
            'exports.completed_packages',
            $packagesValid
                ? Program53HealthSeverity::Info
                : Program53HealthSeverity::Critical,
            $packagesValid
                ? 'Exportações concluídas possuem pacote privado e hashes válidos.'
                : 'Existem exportações concluídas com artefactos ou hashes inválidos.',
            [
                'missing_count' => $missingPackages,
                'invalid_package_hash_count' => $invalidPackageHashes,
                'invalid_metadata_hash_count' => $invalidMetadataHashes,
            ],
        );

        $expiredArtifacts = ReportExport::query()
            ->where('export_profile', TemporalApplicationResultExportService::PROFILE)
            ->where('status', ReportExportStatus::Expired->value)
            ->where('file_path', '!=', '')
            ->count();
        $this->add(
            'exports.expired_artifacts',
            $expiredArtifacts > 0 ? Program53HealthSeverity::Warning : Program53HealthSeverity::Info,
            $expiredArtifacts > 0
                ? 'Existem exportações expiradas com limpeza pendente.'
                : 'Não existem artefactos expirados pendentes.',
            ['count' => $expiredArtifacts],
        );

        $files = Storage::disk('local')->allFiles('report-exports/temporal');
        $partial = count(array_filter(
            $files,
            static fn (string $path): bool => str_ends_with($path, '.partial')
                || str_ends_with($path, '.rewrite'),
        ));
        $this->add(
            'exports.partial_artifacts',
            $partial > 0 ? Program53HealthSeverity::Warning : Program53HealthSeverity::Info,
            $partial > 0 ? 'Existem artefactos parciais no staging.' : 'Não existem artefactos parciais.',
            ['count' => $partial],
        );

        $owners = ReportExport::query()
            ->where('export_profile', TemporalApplicationResultExportService::PROFILE)
            ->pluck('public_id')
            ->filter(static fn (mixed $value): bool => is_string($value))
            ->all();
        $ownerMap = array_fill_keys($owners, true);
        $orphans = [];
        foreach ($files as $path) {
            $parts = explode('/', $path);
            $publicId = $parts[2] ?? null;
            if (is_string($publicId) && ! isset($ownerMap[$publicId])) {
                $orphans[$publicId] = true;
            }
        }
        $this->add(
            'exports.orphan_staging',
            $orphans !== [] ? Program53HealthSeverity::Warning : Program53HealthSeverity::Info,
            $orphans !== [] ? 'Existem diretorias de staging sem owner persistido.' : 'Não existe staging órfão.',
            ['count' => count($orphans)],
        );
    }

    private function inspectScheduler(): void
    {
        $commands = Artisan::all();
        $required = [
            'reports:expire-temporal-exports',
            'access:audit-program-53',
            'program53:benchmark',
            'program53:operational-check',
        ];
        $missing = count(array_filter(
            $required,
            static fn (string $command): bool => ! array_key_exists($command, $commands),
        ));
        $this->add(
            'scheduler.commands',
            $missing === 0 ? Program53HealthSeverity::Info : Program53HealthSeverity::Critical,
            $missing === 0
                ? 'Comandos operacionais do Programa 53 registados.'
                : 'Existem comandos operacionais do Programa 53 em falta.',
            ['missing_count' => $missing],
        );
        $this->add(
            'scheduler.runtime_heartbeat',
            Program53HealthSeverity::Warning,
            'A execução real do scheduler requer heartbeat externo no ambiente alvo.',
        );
    }

    private function inspectConfiguration(): void
    {
        $retention = (int) config('program53.exports.retention_days', 7);
        $this->add(
            'configuration.retention',
            $retention === 7 ? Program53HealthSeverity::Info : Program53HealthSeverity::Warning,
            'Retenção técnica das exportações configurada.',
            ['days' => $retention],
        );

        $timezone = (string) config('app.timezone', 'UTC');
        $validTimezone = in_array($timezone, timezone_identifiers_list(), true);
        $this->add(
            'configuration.timezone',
            $validTimezone ? Program53HealthSeverity::Info : Program53HealthSeverity::Critical,
            $validTimezone ? 'Timezone aplicacional válida.' : 'Timezone aplicacional inválida.',
            ['timezone' => $timezone],
        );
    }

    /** @param array<string, bool|int|string|null> $context */
    private function add(
        string $code,
        Program53HealthSeverity $severity,
        string $message,
        array $context = [],
    ): void {
        $this->findings[] = new Program53HealthFinding(
            $code,
            $severity,
            $message,
            $context,
        );
    }

    private function count(Program53HealthSeverity $severity): int
    {
        return count(array_filter(
            $this->findings,
            static fn (Program53HealthFinding $finding): bool => $finding->severity === $severity,
        ));
    }

    private function validSha256(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/^[a-f0-9]{64}$/', $value) === 1;
    }
}
