<?php

namespace App\Services\Reporting\Temporal;

use App\Contracts\Program53\Program53FaultInjector;
use App\Contracts\Program53\Program53MetricsRecorder;
use App\Data\Program53\Program53OperationalContext;
use App\Data\Reports\ApplicationResultExportPackageOptionsData;
use App\Data\Reports\ApplicationResultExportPreviewData;
use App\Data\Reports\ApplicationResultExportSnapshotData;
use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationResultExportStage;
use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Enums\ExportScope;
use App\Enums\Program53FailureCode;
use App\Enums\ReportAccessType;
use App\Enums\ReportExportStatus;
use App\Enums\ReportFormat;
use App\Enums\ReportRunStatus;
use App\Exceptions\Program53OperationalException;
use App\Http\Middleware\RequestCorrelationId;
use App\Jobs\GenerateApplicationResultExport;
use App\Models\Application;
use App\Models\Contest;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Program53\Resilience\Program53FailureClassifier;
use App\Services\Reporting\ReportAccessLogger;
use App\Services\Reporting\ReportPermissionService;
use App\Services\Support\CanonicalJsonHasher;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class TemporalApplicationResultExportService
{
    public const PROFILE = 'temporal_application_results';

    public const REPORT_CODE = 'temporal_application_results';

    private const RETENTION_DAYS = 7;

    public function __construct(
        private readonly ApplicationResultExportSourceResolver $sources,
        private readonly ApplicationResultExportSnapshotBuilder $snapshots,
        private readonly ApplicationResultExportCheckpointStore $checkpoints,
        private readonly ApplicationResultExportPackageBuilder $packages,
        private readonly ApplicationResultExportPathGuard $paths,
        private readonly ReportPermissionService $permissions,
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly ReportAccessLogger $access,
        private readonly AuditTrailService $audit,
        private readonly CanonicalJsonHasher $hasher,
        private readonly Program53FailureClassifier $failureClassifier,
        private readonly Program53MetricsRecorder $metrics,
        private readonly Program53FaultInjector $faults,
    ) {}

    /** @param array<string, mixed> $data */
    public function preview(User $actor, array $data): ApplicationResultExportPreviewData
    {
        [$contest, $definition, $scope] = $this->authorizeRequest($actor, $data);
        $mode = ApplicationResultExportMode::from((string) $data['mode']);
        $source = $this->sources->resolve(
            $contest,
            $mode,
            $this->sourceParameters($data),
        );
        $formats = $this->stringList($data['formats'] ?? []);
        $datasets = $this->stringList($data['datasets'] ?? []);
        $estimatedApplications = Application::query()
            ->where('contest_id', $contest->getKey())
            ->count();
        $expiresAt = CarbonImmutable::now('UTC')->addDays(self::RETENTION_DAYS);

        $preview = new ApplicationResultExportPreviewData(
            municipalityName: (string) $actor->municipality?->name,
            contestCode: (string) $contest->code,
            contestTitle: (string) $contest->title,
            mode: $mode->value,
            modeLabel: $mode->label(),
            sourceType: $source->sourceType,
            official: $source->official,
            snapshotAt: $source->snapshotAt,
            sourceReferences: $source->sourceReferences,
            formats: $formats,
            datasets: $datasets,
            estimatedApplications: $estimatedApplications,
            sensitiveFieldsIncluded: (bool) ($data['include_sensitive'] ?? false),
            documentFilesRequested: (bool) ($data['include_document_files'] ?? false),
            expiresAt: $expiresAt,
            warnings: $source->warnings,
        );

        $this->recordAudit(
            'application_result_export_previewed',
            null,
            $actor,
            $this->auditMetadata(
                municipalityId: (int) $actor->municipality_id,
                contestId: (int) $contest->getKey(),
                mode: $mode,
                formats: $formats,
                datasets: $datasets,
                sensitive: $preview->sensitiveFieldsIncluded,
                documents: $preview->documentFilesRequested,
                extra: [
                    'source_type' => $source->sourceType,
                    'official' => $source->official,
                    'estimated_applications' => $estimatedApplications,
                ],
            ),
        );

        unset($definition, $scope);

        return $preview;
    }

    /** @param array<string, mixed> $data */
    public function request(User $actor, array $data): ReportExport
    {
        [$contest, $definition, $scope] = $this->authorizeRequest($actor, $data);
        $mode = ApplicationResultExportMode::from((string) $data['mode']);
        $source = $this->sources->resolve(
            $contest,
            $mode,
            $this->sourceParameters($data),
        );
        $formats = $this->stringList($data['formats'] ?? []);
        $datasets = $this->stringList($data['datasets'] ?? []);
        $parameters = $this->sourceParameters($data);
        $idempotencyKey = $this->idempotencyKey($actor, $contest, $data);
        $sensitive = (bool) ($data['include_sensitive'] ?? false);
        $documents = (bool) ($data['include_document_files'] ?? false);
        $operationId = (string) Str::orderedUuid();
        [$requestId, $correlationId] = $this->requestIdentifiers();
        $created = false;

        try {
            $export = DB::transaction(function () use (
                $actor,
                $contest,
                $definition,
                $scope,
                $mode,
                $source,
                $formats,
                $datasets,
                $parameters,
                $idempotencyKey,
                $sensitive,
                $documents,
                $data,
                $operationId,
                $requestId,
                $correlationId,
                &$created,
            ): ReportExport {
                $existing = ReportExport::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();
                if ($existing instanceof ReportExport) {
                    return $existing;
                }

                $requestedAt = CarbonImmutable::now('UTC');
                $run = new ReportRun;
                $run->forceFill([
                    'public_id' => (string) Str::orderedUuid(),
                    'report_definition_id' => $definition->getKey(),
                    'user_id' => $actor->getKey(),
                    'status' => ReportRunStatus::Started,
                    'format' => ReportFormat::Zip,
                    'scope' => $scope,
                    'filters' => [
                        'contest_id' => (int) $contest->getKey(),
                        'mode' => $mode->value,
                        'source_parameters' => $parameters,
                        'formats' => $formats,
                        'datasets' => $datasets,
                    ],
                    'started_at' => $requestedAt,
                ])->save();

                $export = new ReportExport;
                $export->forceFill([
                    'public_id' => (string) Str::orderedUuid(),
                    'report_run_id' => $run->getKey(),
                    'user_id' => $actor->getKey(),
                    'municipality_id' => $actor->municipality_id,
                    'contest_id' => $contest->getKey(),
                    'export_profile' => self::PROFILE,
                    'export_mode' => $mode,
                    'status' => ReportExportStatus::Pending,
                    'requested_format' => ReportFormat::Zip,
                    'format' => ReportFormat::Zip,
                    'scope' => $scope,
                    'disk' => 'local',
                    'file_path' => '',
                    'file_name' => '',
                    'processing_stage' => ApplicationResultExportStage::Queued,
                    'progress' => 0,
                    'expires_at' => $requestedAt->addDays(self::RETENTION_DAYS),
                    'source_metadata' => [
                        'operational' => [
                            'operation_id' => $operationId,
                            'request_id' => $requestId,
                            'correlation_id' => $correlationId,
                            'attempt' => 0,
                        ],
                        'parameters' => $parameters,
                        'request_options' => [
                            'csv_delimiter' => $this->csvDelimiter($data),
                            'csv_bom' => (bool) ($data['csv_bom'] ?? false),
                            'include_unchanged' => (bool) ($data['include_unchanged'] ?? false),
                            'changed_documents_only' => (bool) ($data['changed_documents_only'] ?? false),
                            'sensitive_confirmed' => (bool) ($data['sensitive_confirmed'] ?? false),
                            'document_files_confirmed' => (bool) ($data['document_files_confirmed'] ?? false),
                        ],
                        'validated_source' => [
                            'source_type' => $source->sourceType,
                            'official' => $source->official,
                            'source_references' => $source->sourceReferences,
                        ],
                    ],
                    'idempotency_key' => $idempotencyKey,
                    'formats' => $formats,
                    'datasets' => $datasets,
                    'sensitive_fields_included' => $sensitive,
                    'document_files_requested' => $documents,
                    'document_files_included' => false,
                ])->save();

                $created = true;

                return $export;
            });
        } catch (QueryException $exception) {
            $export = ReportExport::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if (! $export instanceof ReportExport) {
                throw $exception;
            }
        }

        if (! $created) {
            return $export->refresh();
        }

        GenerateApplicationResultExport::dispatch((int) $export->getKey())
            ->afterCommit();

        $safeFilters = [
            'contest_id' => (int) $contest->getKey(),
            'mode' => $mode->value,
            'formats' => $formats,
            'datasets' => $datasets,
        ];
        $this->access->record(
            $actor,
            ReportAccessType::ExportReport,
            $definition,
            run: $export->run,
            export: $export,
            filters: $safeFilters,
            format: ReportFormat::Zip,
            scope: $scope,
        );
        $metadata = $this->auditMetadata(
            municipalityId: (int) $actor->municipality_id,
            contestId: (int) $contest->getKey(),
            mode: $mode,
            formats: $formats,
            datasets: $datasets,
            sensitive: $sensitive,
            documents: $documents,
            extra: [
                'export_public_id' => $export->public_id,
                'source_type' => $source->sourceType,
                'official' => $source->official,
            ],
        );
        $this->recordAudit('application_result_export_requested', $export, $actor, $metadata);
        if ($sensitive) {
            $this->recordAudit('sensitive_application_result_export_requested', $export, $actor, $metadata);
        }
        if ($documents) {
            $this->recordAudit('document_dossier_export_requested', $export, $actor, $metadata);
        }

        return $export->refresh();
    }

    public function process(int $exportId): void
    {
        $export = $this->start($exportId);
        if (! $export instanceof ReportExport) {
            return;
        }

        $baseDirectory = $this->baseDirectory($export);
        $finalPath = null;
        $startedAt = hrtime(true);
        $context = $this->operationalContext(
            $export,
            ApplicationResultExportStage::Snapshotting->value,
        );

        if ($context->attempt > 1) {
            $this->metrics->record(
                'export_retries',
                1,
                $context,
                ['operation' => 'generate'],
            );
        }

        try {
            Storage::disk('local')->deleteDirectory($baseDirectory.'/staging/package');
            Storage::disk('local')->deleteDirectory($this->finalDirectory($export));

            $contest = Contest::query()
                ->whereKey($export->contest_id)
                ->whereHas('program', fn ($program) => $program
                    ->where('municipality_id', $export->municipality_id))
                ->firstOrFail();
            $mode = $export->export_mode;
            if (! $mode instanceof ApplicationResultExportMode) {
                throw new RuntimeException('A exportação não possui modo temporal válido.');
            }

            $metadata = $this->metadata($export);
            $parameters = $this->array($metadata['parameters'] ?? []);
            $retryCapturedAt = $this->retryCapturedAt($mode, $metadata);

            if ($retryCapturedAt !== null) {
                $parameters['captured_at'] = $retryCapturedAt;
            }

            $source = $this->sources->resolve(
                $contest,
                $mode,
                $parameters,
            );
            $this->faults->checkpoint(
                'after_source_resolution',
                $context->withStage('source_resolution'),
            );

            $snapshotStartedAt = hrtime(true);
            $sourceDirectory = $baseDirectory.'/staging/source';
            $snapshot = $this->checkpoints->restore(
                $source,
                $sourceDirectory,
                $this->array($metadata['export_checkpoint'] ?? []),
            );
            $reusedSnapshot = $snapshot !== null;
            if (! $snapshot instanceof ApplicationResultExportSnapshotData) {
                if ($retryCapturedAt !== null) {
                    unset($parameters['captured_at']);

                    $source = $this->sources->resolve(
                        $contest,
                        $mode,
                        $parameters,
                    );
                }

                $snapshot = $this->snapshots->build(
                    $source,
                    $sourceDirectory,
                    $export->sensitive_fields_included,
                    (bool) data_get($metadata, 'request_options.include_unchanged', false),
                    $context,
                );
            }

            $checkpoint = $this->checkpoints->capture($snapshot);
            $this->updateStage(
                $exportId,
                ApplicationResultExportStage::Rendering,
                35,
                [
                    'resolved_source' => $source->fingerprintPayload(),
                    'source_references' => $source->sourceReferences,
                    'snapshot_counts' => $snapshot->counts,
                    'warnings' => $snapshot->warnings,
                    'export_checkpoint' => $checkpoint,
                    'snapshot_reused' => $reusedSnapshot,
                ],
                snapshotAt: $source->snapshotAt,
                sourceFingerprint: $snapshot->sourceFingerprint,
            );
            $snapshotDuration = $this->elapsedMilliseconds($snapshotStartedAt);
            $this->metrics->record(
                'snapshot_duration',
                $snapshotDuration,
                $context->withStage('snapshot'),
                ['reused' => $reusedSnapshot],
            );
            foreach ($snapshot->counts as $dataset => $count) {
                $this->metrics->record(
                    'rows_by_dataset',
                    $count,
                    $context->withStage('snapshot'),
                    ['dataset' => $dataset],
                );
            }
            $this->faults->checkpoint(
                'after_snapshot_checksum',
                $context->withStage('snapshot'),
            );
            $this->recordAudit(
                'application_result_export_snapshot_created',
                $export,
                $export->user,
                [
                    'export_public_id' => $export->public_id,
                    'municipality_id' => $export->municipality_id,
                    'contest_id' => $export->contest_id,
                    'mode' => $mode->value,
                    'source_fingerprint' => $snapshot->sourceFingerprint,
                    'counts' => $snapshot->counts,
                    'snapshot_at' => $source->snapshotAt->toIso8601String(),
                ],
            );

            $packageStartedAt = hrtime(true);
            $this->updateStage(
                $exportId,
                ApplicationResultExportStage::Packaging,
                70,
            );
            $options = $this->packageOptions($export, $metadata);
            $package = $this->packages->build(
                $snapshot,
                $options,
                $baseDirectory.'/staging/package',
                $context,
            );
            $this->metrics->record(
                'package_duration',
                $this->elapsedMilliseconds($packageStartedAt),
                $context->withStage('package'),
            );

            $finalDirectory = $this->finalDirectory($export);
            $finalPath = $finalDirectory.'/'.$package->fileName;
            $disk = Storage::disk('local');
            if (! $disk->makeDirectory($finalDirectory)) {
                throw new RuntimeException('Não foi possível preparar o destino privado da exportação.');
            }
            $this->faults->checkpoint(
                'before_atomic_move',
                $context->withStage('publish'),
            );
            if (! $disk->move($package->packagePath, $finalPath)) {
                throw new RuntimeException('Não foi possível publicar atomicamente a exportação.');
            }
            $this->paths->assertRelative($finalPath);
            $publishedHash = hash_file('sha256', $disk->path($finalPath));
            if (! is_string($publishedHash) || ! hash_equals($package->packageSha256, $publishedHash)) {
                throw new RuntimeException('O hash do pacote publicado não é válido.');
            }
            $this->faults->checkpoint(
                'after_atomic_move_before_completion',
                $context->withStage('publish'),
            );

            $this->complete(
                $exportId,
                $finalPath,
                $package->fileName,
                $package->size,
                $package->manifestSha256,
                $package->packageSha256,
                $package->documentFilesIncluded,
                $snapshot->counts,
                $package->warnings,
            );
            $disk->deleteDirectory($baseDirectory.'/staging');
            $this->metrics->record(
                'export_duration',
                $this->elapsedMilliseconds($startedAt),
                $context->withStage('completed'),
                ['result' => 'completed'],
            );
            $this->metrics->record(
                'peak_memory',
                memory_get_peak_usage(true),
                $context->withStage('completed'),
            );
        } catch (Throwable $exception) {
            if (is_string($finalPath)) {
                Storage::disk('local')->delete($finalPath);
            }
            $this->markFailed($exportId, $exception);
            $failure = $this->failureClassifier->classify($exception);
            $this->metrics->record(
                'export_failures',
                1,
                $context->withStage('failed'),
                [
                    'failure_code' => $failure->code->value,
                    'result' => $failure->disposition->value,
                ],
            );

            throw $exception;
        }
    }

    public function markFailed(int $exportId, Throwable $exception): void
    {
        $failure = $this->failureClassifier->classify($exception);
        $failureCode = $failure->code->value;
        $export = DB::transaction(function () use ($exportId, $failure, $failureCode): ?ReportExport {
            $locked = ReportExport::query()->whereKey($exportId)->lockForUpdate()->first();
            if (! $locked instanceof ReportExport || in_array($locked->status, [
                ReportExportStatus::Completed,
                ReportExportStatus::Expired,
                ReportExportStatus::Cancelled,
            ], true)) {
                return null;
            }
            if (
                $locked->status === ReportExportStatus::Failed
                && $locked->failure_code === $failureCode
            ) {
                return null;
            }

            $locked->forceFill([
                'status' => ReportExportStatus::Failed,
                'processing_stage' => ApplicationResultExportStage::Failed,
                'failure_code' => $failureCode,
                'error_message' => $failure->safeMessage(),
                'failed_at' => now(),
                'progress' => 0,
                'file_path' => '',
                'file_name' => '',
                'file_size' => null,
            ])->save();
            $locked->run()->update([
                'status' => ReportRunStatus::Failed->value,
                'failed_at' => now(),
                'error_message' => $failure->safeMessage(),
            ]);

            return $locked->loadMissing('user');
        });

        if (! $export instanceof ReportExport) {
            return;
        }

        $baseDirectory = $this->baseDirectory($export);
        Storage::disk('local')->deleteDirectory($baseDirectory.'/staging/package');
        if (! $failure->retryable()) {
            Storage::disk('local')->deleteDirectory($baseDirectory.'/staging');
        }
        Storage::disk('local')->deleteDirectory($this->finalDirectory($export));
        $this->recordAudit(
            'application_result_export_failed',
            $export,
            $export->user,
            [
                'export_public_id' => $export->public_id,
                'municipality_id' => $export->municipality_id,
                'contest_id' => $export->contest_id,
                'mode' => $export->export_mode?->value,
                'failure_code' => $failureCode,
                'failure_disposition' => $failure->disposition->value,
                'snapshot_preserved' => $failure->retryable(),
            ],
            AuditEventSeverity::Warning,
        );
    }

    public function expire(int $exportId): bool
    {
        $candidate = ReportExport::query()->find($exportId);
        if (! $candidate instanceof ReportExport) {
            return false;
        }
        $context = $this->operationalContext($candidate, 'expiration');
        $this->faults->checkpoint('before_expiration_lock', $context);
        $startedAt = hrtime(true);

        $export = DB::transaction(function () use ($exportId): ?ReportExport {
            $locked = ReportExport::query()->whereKey($exportId)->lockForUpdate()->first();
            if (
                ! $locked instanceof ReportExport
                || ! $locked->isTemporalApplicationResultExport()
            ) {
                return null;
            }

            if ($locked->status === ReportExportStatus::Expired) {
                return $locked->file_path !== ''
                    ? $locked->loadMissing('user')
                    : null;
            }

            if (
                $locked->status !== ReportExportStatus::Completed
                || $locked->expires_at === null
                || $locked->expires_at->isFuture()
                || $locked->downloaded_at?->greaterThan(now()->subMinutes(5))
            ) {
                return null;
            }

            $locked->forceFill([
                'status' => ReportExportStatus::Expired,
                'processing_stage' => ApplicationResultExportStage::Expired,
                'progress' => 100,
            ])->save();

            return $locked->loadMissing('user');
        });

        if (! $export instanceof ReportExport) {
            return false;
        }

        $this->faults->checkpoint(
            'after_database_expired_before_file_delete',
            $context,
        );
        $path = ltrim((string) $export->file_path, '/');
        $disk = Storage::disk('local');
        if (
            $path !== ''
            && ! str_contains($path, '..')
            && $disk->exists($path)
            && ! $disk->delete($path)
        ) {
            throw new Program53OperationalException(
                Program53FailureCode::StorageUnavailable,
            );
        }
        $this->faults->checkpoint(
            'during_staging_cleanup',
            $context,
        );
        $disk->deleteDirectory($this->baseDirectory($export));

        DB::transaction(function () use ($exportId): void {
            $locked = ReportExport::query()->whereKey($exportId)->lockForUpdate()->first();
            if (
                ! $locked instanceof ReportExport
                || $locked->status !== ReportExportStatus::Expired
            ) {
                return;
            }

            $locked->forceFill([
                'file_path' => '',
                'file_name' => '',
                'file_size' => null,
            ])->save();
        });

        $this->recordAudit(
            'application_result_export_expired',
            $export,
            null,
            [
                'export_public_id' => $export->public_id,
                'municipality_id' => $export->municipality_id,
                'contest_id' => $export->contest_id,
                'mode' => $export->export_mode?->value,
                'expired_at' => now()->utc()->toIso8601String(),
            ],
            subject: $export->user,
        );
        $this->metrics->record(
            'expiration_duration',
            $this->elapsedMilliseconds($startedAt),
            $context->withStage('expired'),
            ['result' => 'completed'],
        );

        return true;
    }

    public function markExpirationFailed(int $exportId, Throwable $exception): void
    {
        $export = ReportExport::query()->with('user')->find($exportId);
        if (! $export instanceof ReportExport) {
            return;
        }

        $failure = $this->failureClassifier->classify($exception);
        $this->metrics->record(
            'expiration_failures',
            1,
            $this->operationalContext($export, 'expiration_failed'),
            ['failure_code' => $failure->code->value],
        );
        $this->recordAudit(
            'application_result_export_expiration_failed',
            $export,
            null,
            [
                'export_public_id' => $export->public_id,
                'municipality_id' => $export->municipality_id,
                'contest_id' => $export->contest_id,
                'failure_code' => $failure->code->value,
            ],
            AuditEventSeverity::Warning,
            $export->user,
        );
    }

    /**
     * Recupera o instante da origem operacional anterior exclusivamente para
     * validar um checkpoint preservado. Um checkpoint inválido é reconstruído
     * com uma nova origem atual.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function retryCapturedAt(
        ApplicationResultExportMode $mode,
        array $metadata,
    ): ?string {
        if ($mode !== ApplicationResultExportMode::CurrentState) {
            return null;
        }

        $capturedAt = data_get(
            $metadata,
            'resolved_source.source_references.captured_at',
        );

        if (! is_string($capturedAt) || trim($capturedAt) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($capturedAt, 'UTC')
                ->utc()
                ->toIso8601String();
        } catch (Throwable) {
            return null;
        }
    }

    private function start(int $exportId): ?ReportExport
    {
        $export = DB::transaction(function () use ($exportId): ?ReportExport {
            $locked = ReportExport::query()
                ->whereKey($exportId)
                ->lockForUpdate()
                ->first();
            if (
                ! $locked instanceof ReportExport
                || ! $locked->isTemporalApplicationResultExport()
                || in_array($locked->status, [
                    ReportExportStatus::Completed,
                    ReportExportStatus::Expired,
                    ReportExportStatus::Cancelled,
                ], true)
            ) {
                return null;
            }
            $staleAfter = max(60, (int) config(
                'program53.exports.stale_after_seconds',
                2100,
            ));
            if (
                $locked->status === ReportExportStatus::Processing
                && $locked->updated_at?->greaterThan(now()->subSeconds($staleAfter))
            ) {
                return null;
            }

            $metadata = $this->metadata($locked);
            $operational = $this->array($metadata['operational'] ?? []);
            $attempt = max(0, (int) ($operational['attempt'] ?? 0)) + 1;
            $operationId = is_string($operational['operation_id'] ?? null)
                ? $operational['operation_id']
                : (string) Str::orderedUuid();

            $locked->forceFill([
                'status' => ReportExportStatus::Processing,
                'processing_stage' => ApplicationResultExportStage::Snapshotting,
                'progress' => 10,
                'started_at' => now(),
                'failed_at' => null,
                'failure_code' => null,
                'error_message' => null,
                'completed_at' => null,
                'file_path' => '',
                'file_name' => '',
                'file_size' => null,
                'source_metadata' => [
                    ...$metadata,
                    'operational' => [
                        ...$operational,
                        'operation_id' => $operationId,
                        'attempt' => $attempt,
                    ],
                ],
            ])->save();
            $locked->run()->update([
                'status' => ReportRunStatus::Started->value,
                'failed_at' => null,
                'error_message' => null,
            ]);

            return $locked->loadMissing(['user', 'contest']);
        });

        if ($export instanceof ReportExport) {
            $this->recordAudit(
                'application_result_export_started',
                $export,
                $export->user,
                [
                    'export_public_id' => $export->public_id,
                    'municipality_id' => $export->municipality_id,
                    'contest_id' => $export->contest_id,
                    'mode' => $export->export_mode?->value,
                    'started_at' => $export->started_at?->utc()->toIso8601String(),
                ],
            );
        }

        return $export;
    }

    /** @param array<string, mixed> $metadata */
    private function updateStage(
        int $exportId,
        ApplicationResultExportStage $stage,
        int $progress,
        array $metadata = [],
        ?CarbonImmutable $snapshotAt = null,
        ?string $sourceFingerprint = null,
    ): void {
        DB::transaction(function () use (
            $exportId,
            $stage,
            $progress,
            $metadata,
            $snapshotAt,
            $sourceFingerprint,
        ): void {
            $locked = ReportExport::query()->whereKey($exportId)->lockForUpdate()->firstOrFail();
            if ($locked->status !== ReportExportStatus::Processing) {
                throw new RuntimeException('A exportação deixou de estar em processamento.');
            }

            $current = $this->metadata($locked);
            $locked->forceFill([
                'processing_stage' => $stage,
                'progress' => max(0, min(100, $progress)),
                'snapshot_at' => $snapshotAt ?? $locked->snapshot_at,
                'source_fingerprint' => $sourceFingerprint ?? $locked->source_fingerprint,
                'source_metadata' => [...$current, ...$metadata],
            ])->save();
        });
    }

    /**
     * @param  array<string, int>  $counts
     * @param  list<string>  $warnings
     */
    private function complete(
        int $exportId,
        string $path,
        string $fileName,
        int $size,
        string $manifestSha256,
        string $packageSha256,
        bool $documentFilesIncluded,
        array $counts,
        array $warnings,
    ): void {
        $export = DB::transaction(function () use (
            $exportId,
            $path,
            $fileName,
            $size,
            $manifestSha256,
            $packageSha256,
            $documentFilesIncluded,
            $counts,
            $warnings,
        ): ReportExport {
            $locked = ReportExport::query()->whereKey($exportId)->lockForUpdate()->firstOrFail();
            if ($locked->status !== ReportExportStatus::Processing) {
                throw new RuntimeException('A exportação não pode ser concluída no estado atual.');
            }

            $metadata = $this->metadata($locked);
            $locked->forceFill([
                'status' => ReportExportStatus::Completed,
                'processing_stage' => ApplicationResultExportStage::Completed,
                'progress' => 100,
                'file_path' => $path,
                'file_name' => $fileName,
                'file_size' => $size,
                'manifest_sha256' => $manifestSha256,
                'package_sha256' => $packageSha256,
                'document_files_included' => $documentFilesIncluded,
                'completed_at' => now(),
                'source_metadata' => [
                    ...$metadata,
                    'counts' => $counts,
                    'warnings' => $warnings,
                ],
            ])->save();
            $locked->run()->update([
                'status' => ReportRunStatus::Completed->value,
                'row_count' => $counts['applications'] ?? 0,
                'completed_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ]);

            return $locked->loadMissing('user');
        });

        $this->recordAudit(
            'application_result_export_completed',
            $export,
            $export->user,
            [
                'export_public_id' => $export->public_id,
                'municipality_id' => $export->municipality_id,
                'contest_id' => $export->contest_id,
                'mode' => $export->export_mode?->value,
                'formats' => $export->formats,
                'datasets' => $export->datasets,
                'counts' => $counts,
                'sensitive_fields_included' => $export->sensitive_fields_included,
                'document_files_included' => $documentFilesIncluded,
                'source_fingerprint' => $export->source_fingerprint,
                'manifest_sha256' => $manifestSha256,
                'package_sha256' => $packageSha256,
                'file_size' => $size,
                'completed_at' => $export->completed_at?->utc()->toIso8601String(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{Contest, ReportDefinition, ExportScope}
     */
    private function authorizeRequest(User $actor, array $data): array
    {
        if (
            $actor->municipality_id === null
            || $actor->hasRole(['candidate', 'auditor'])
            || ! $this->permissions->canAccessApplicationExportCatalog($actor)
        ) {
            throw new AuthorizationException;
        }

        $contest = $this->municipalScope
            ->contests(Contest::query(), $actor)
            ->whereKey((int) ($data['contest_id'] ?? 0))
            ->with('program.municipality')
            ->first();
        if (! $contest instanceof Contest) {
            throw new AuthorizationException;
        }

        $definition = ReportDefinition::query()
            ->where('code', self::REPORT_CODE)
            ->where('is_active', true)
            ->firstOrFail();
        $sensitive = (bool) ($data['include_sensitive'] ?? false);
        $documents = (bool) ($data['include_document_files'] ?? false);
        $scope = $sensitive ? ExportScope::Nominal : ExportScope::Pseudonymized;

        if (
            ! $this->permissions->canExport($actor, $definition, $scope)
            || (($sensitive || $documents) && ! $actor->hasPermission('reports.export_sensitive'))
        ) {
            throw new AuthorizationException;
        }

        return [$contest, $definition, $scope];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function sourceParameters(array $data): array
    {
        $parameters = [];
        foreach ([
            'batch_public_id',
            'base_batch_public_id',
            'target_batch_public_id',
            'phase',
            'as_of',
            'since',
        ] as $key) {
            $value = $data[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                $parameters[$key] = trim($value);
            }
        }

        ksort($parameters);

        return $parameters;
    }

    /** @param array<string, mixed> $data */
    private function idempotencyKey(User $actor, Contest $contest, array $data): string
    {
        return $this->hasher->hash([
            'profile' => self::PROFILE,
            'actor_id' => (int) $actor->getKey(),
            'municipality_id' => (int) $actor->municipality_id,
            'contest_id' => (int) $contest->getKey(),
            'client_token' => (string) ($data['idempotency_token'] ?? ''),
            'mode' => (string) ($data['mode'] ?? ''),
            'source_parameters' => $this->sourceParameters($data),
            'formats' => $this->stringList($data['formats'] ?? []),
            'datasets' => $this->stringList($data['datasets'] ?? []),
            'include_sensitive' => (bool) ($data['include_sensitive'] ?? false),
            'include_document_files' => (bool) ($data['include_document_files'] ?? false),
            'changed_documents_only' => (bool) ($data['changed_documents_only'] ?? false),
            'include_unchanged' => (bool) ($data['include_unchanged'] ?? false),
            'csv_delimiter' => $this->csvDelimiter($data),
            'csv_bom' => (bool) ($data['csv_bom'] ?? false),
        ]);
    }

    /** @param array<string, mixed> $metadata */
    private function packageOptions(
        ReportExport $export,
        array $metadata,
    ): ApplicationResultExportPackageOptionsData {
        $formats = array_map(
            static fn (string $format): ApplicationResultExportFormat => ApplicationResultExportFormat::from($format),
            $this->stringList($export->formats ?? []),
        );
        $datasets = array_map(
            static fn (string $dataset): ApplicationResultExportDataset => ApplicationResultExportDataset::from($dataset),
            $this->stringList($export->datasets ?? []),
        );
        $options = $this->array($metadata['request_options'] ?? []);
        $createdAt = $export->created_at;
        $expiresAt = $export->expires_at;
        if ($createdAt === null || $expiresAt === null) {
            throw new RuntimeException('A exportação não possui retenção temporal válida.');
        }

        return new ApplicationResultExportPackageOptionsData(
            exportPublicId: (string) $export->public_id,
            formats: $formats,
            datasets: $datasets,
            generatedAt: CarbonImmutable::instance($createdAt)->utc(),
            expiresAt: CarbonImmutable::instance($expiresAt)->utc(),
            includeSensitive: $export->sensitive_fields_included,
            sensitiveConfirmed: (bool) ($options['sensitive_confirmed'] ?? false),
            includeDocumentFiles: $export->document_files_requested,
            changedDocumentsOnly: (bool) ($options['changed_documents_only'] ?? false),
            csvDelimiter: (string) ($options['csv_delimiter'] ?? ';'),
            csvBom: (bool) ($options['csv_bom'] ?? false),
        );
    }

    private function baseDirectory(ReportExport $export): string
    {
        $directory = 'report-exports/temporal/'.(string) $export->public_id;
        $this->paths->assertRelative($directory);

        return $directory;
    }

    private function finalDirectory(ReportExport $export): string
    {
        $createdAt = $export->created_at ?? now();
        $directory = 'reports/'.$createdAt->format('Y/m').'/'.(string) $export->public_id;
        $this->paths->assertRelative($directory);

        return $directory;
    }

    /** @return array<string, mixed> */
    private function metadata(ReportExport $export): array
    {
        return is_array($export->source_metadata)
            ? $export->source_metadata
            : [];
    }

    /** @return array<string, mixed> */
    private function array(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $item): string => (string) $item,
            $value,
        ));
    }

    /** @param array<string, mixed> $data */
    private function csvDelimiter(array $data): string
    {
        return match ((string) ($data['csv_delimiter'] ?? 'semicolon')) {
            'comma' => ',',
            'tab' => "\t",
            default => ';',
        };
    }

    /** @return array{string|null, string|null} */
    private function requestIdentifiers(): array
    {
        if (! app()->bound('request')) {
            return [null, null];
        }

        $request = app(Request::class);
        $requestId = $this->safeIdentifier(
            $request->attributes->get(RequestCorrelationId::ATTRIBUTE),
        );
        $correlationId = $this->safeIdentifier(
            $request->headers->get('X-Correlation-ID'),
        ) ?? $requestId;

        return [$requestId, $correlationId];
    }

    private function operationalContext(
        ReportExport $export,
        string $stage,
    ): Program53OperationalContext {
        $metadata = $this->metadata($export);
        $operational = $this->array($metadata['operational'] ?? []);

        return new Program53OperationalContext(
            operationId: $this->safeIdentifier($operational['operation_id'] ?? null)
                ?? 'export-'.$export->getKey(),
            requestId: $this->safeIdentifier($operational['request_id'] ?? null),
            correlationId: $this->safeIdentifier($operational['correlation_id'] ?? null),
            municipalityId: $export->municipality_id !== null
                ? (int) $export->municipality_id
                : null,
            contestId: $export->contest_id !== null
                ? (int) $export->contest_id
                : null,
            exportId: (int) $export->getKey(),
            attempt: max(1, (int) ($operational['attempt'] ?? 1)),
            stage: $stage,
        );
    }

    private function safeIdentifier(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return preg_match('/^[A-Za-z0-9][A-Za-z0-9_.:\-]{0,119}$/', $value) === 1
            ? $value
            : null;
    }

    private function elapsedMilliseconds(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 3);
    }

    /**
     * @param  list<string>  $formats
     * @param  list<string>  $datasets
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function auditMetadata(
        int $municipalityId,
        int $contestId,
        ApplicationResultExportMode $mode,
        array $formats,
        array $datasets,
        bool $sensitive,
        bool $documents,
        array $extra = [],
    ): array {
        return [
            'municipality_id' => $municipalityId,
            'contest_id' => $contestId,
            'mode' => $mode->value,
            'formats' => $formats,
            'datasets' => $datasets,
            'sensitive_fields_included' => $sensitive,
            'document_files_requested' => $documents,
            ...$extra,
        ];
    }

    /** @param array<string, mixed> $metadata */
    private function recordAudit(
        string $event,
        ?ReportExport $export,
        ?User $actor,
        array $metadata,
        AuditEventSeverity $severity = AuditEventSeverity::Info,
        ?User $subject = null,
    ): void {
        $this->audit->record(
            eventCode: $event,
            auditable: $export,
            category: AuditEventCategory::Reports,
            severity: $severity,
            description: match ($event) {
                'application_result_export_previewed' => 'Pré-visualização de exportação temporal realizada.',
                'application_result_export_requested' => 'Exportação temporal pedida.',
                'sensitive_application_result_export_requested' => 'Exportação temporal sensível pedida.',
                'document_dossier_export_requested' => 'Dossier documental municipal pedido.',
                'application_result_export_started' => 'Geração de exportação temporal iniciada.',
                'application_result_export_snapshot_created' => 'Snapshot canónico da exportação temporal criado.',
                'application_result_export_completed' => 'Exportação temporal concluída.',
                'application_result_export_failed' => 'Exportação temporal falhou de forma controlada.',
                'application_result_export_expired' => 'Exportação temporal expirada e artefactos eliminados.',
                'application_result_export_expiration_failed' => 'A limpeza de uma exportação temporal falhou de forma controlada.',
                default => 'Evento de exportação temporal registado.',
            },
            metadata: $metadata,
            subject: $subject,
            actor: $actor,
            useAuthenticatedUser: false,
        );
    }
}
