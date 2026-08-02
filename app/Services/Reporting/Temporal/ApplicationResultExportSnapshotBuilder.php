<?php

namespace App\Services\Reporting\Temporal;

use App\Contracts\Program53\Program53FaultInjector;
use App\Data\Program53\Program53OperationalContext;
use App\Data\Reports\ApplicationResultExportSnapshotData;
use App\Data\Reports\ApplicationResultExportSourceData;
use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewStatus;
use App\Enums\ApplicationStatus;
use App\Enums\CorrectionRevalidationAggregateResult;
use App\Enums\DocumentStatus;
use App\Models\AdministrativeDecision;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReview;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewBatchItem;
use App\Models\ApplicationReviewPublication;
use App\Models\ApplicationReviewPublicationResult;
use App\Models\ApplicationScore;
use App\Models\CorrectionRequest;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\EligibilityCheck;
use App\Models\RequiredDocument;
use App\Services\Support\CanonicalJsonHasher;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;
use RuntimeException;
use Throwable;

final class ApplicationResultExportSnapshotBuilder
{
    private const CHUNK_SIZE = 250;

    public function __construct(
        private readonly CanonicalNdjsonStore $store,
        private readonly ApplicationResultExportFieldCatalog $catalog,
        private readonly ApplicationResultExportComparator $comparator,
        private readonly CanonicalJsonHasher $hasher,
        private readonly Program53FaultInjector $faults,
    ) {}

    /**
     * @throws JsonException
     */
    public function build(
        ApplicationResultExportSourceData $source,
        string $stagingDirectory,
        bool $includeSensitive = false,
        bool $includeUnchanged = false,
        ?Program53OperationalContext $context = null,
    ): ApplicationResultExportSnapshotData {
        $this->store->deleteDirectory($stagingDirectory);
        $this->store->createDirectory($stagingDirectory);

        try {
            [$paths, $counts] = $source->mode->isDelta()
                ? $this->buildDelta(
                    $source,
                    $stagingDirectory,
                    $includeSensitive,
                    $includeUnchanged,
                )
                : $this->buildSingleSource(
                    $source,
                    $stagingDirectory,
                    $includeSensitive,
                );

            if ($context instanceof Program53OperationalContext) {
                $this->faults->checkpoint(
                    'mid_ndjson_snapshot',
                    $context->withStage('snapshot'),
                );
            }

            $preFingerprintChecksums = $this->checksums($paths);
            $sourceFingerprint = $this->hasher->hash([
                'source' => $source->fingerprintPayload(),
                'datasets' => $preFingerprintChecksums,
            ]);

            $applicationsPath = $paths[ApplicationResultExportDataset::Applications->value];
            $this->store->rewrite(
                $applicationsPath,
                static function (array $row) use ($sourceFingerprint): array {
                    $row['source_fingerprint'] = $sourceFingerprint;

                    return $row;
                },
            );

            return new ApplicationResultExportSnapshotData(
                source: $source,
                datasetPaths: $paths,
                counts: $counts,
                checksums: $this->checksums($paths),
                sourceFingerprint: $sourceFingerprint,
                warnings: $source->warnings,
            );
        } catch (Throwable $exception) {
            $this->store->deleteDirectory($stagingDirectory);

            throw $exception;
        }
    }

    /**
     * @return array{array<string, string>, array<string, int>}
     *
     * @throws JsonException
     */
    private function buildSingleSource(
        ApplicationResultExportSourceData $source,
        string $directory,
        bool $includeSensitive,
    ): array {
        $paths = $this->datasetPaths($directory);

        $write = function () use (
            $source,
            $paths,
            $includeSensitive,
        ): array {
            $rows = match ($source->mode) {
                ApplicationResultExportMode::CurrentState => $this->currentRows(
                    $source,
                    $includeSensitive,
                ),
                ApplicationResultExportMode::SealedBatch,
                ApplicationResultExportMode::PhaseSnapshot => $this->batchRows(
                    $source,
                    $this->requiredBatchId($source),
                    $includeSensitive,
                ),
                ApplicationResultExportMode::FinalResult => $this->finalResultRows(
                    $source,
                    $includeSensitive,
                ),
                default => throw new RuntimeException('Modo temporal incompatível com fonte única.'),
            };

            return $this->writeDatasets($paths, $rows);
        };

        $counts = in_array($source->mode, [
            ApplicationResultExportMode::CurrentState,
            ApplicationResultExportMode::FinalResult,
        ], true)
            ? $this->consistentRead($write)
            : $write();

        return [$paths, $counts];
    }

    /**
     * @return array{array<string, string>, array<string, int>}
     *
     * @throws JsonException
     */
    private function buildDelta(
        ApplicationResultExportSourceData $source,
        string $directory,
        bool $includeSensitive,
        bool $includeUnchanged,
    ): array {
        if ($source->baseBatchId === null || $source->targetBatchId === null) {
            throw ValidationException::withMessages([
                'source' => 'O delta não possui as duas fontes imutáveis obrigatórias.',
                'failure_code' => 'source_not_found',
            ]);
        }

        $baseDirectory = $directory.'/base';
        $targetDirectory = $directory.'/target';
        $this->store->createDirectory($baseDirectory);
        $this->store->createDirectory($targetDirectory);
        $basePaths = $this->datasetPaths($baseDirectory);
        $targetPaths = $this->datasetPaths($targetDirectory);
        $this->writeDatasets(
            $basePaths,
            $this->batchRows($source, $source->baseBatchId, $includeSensitive),
        );
        $targetCounts = $this->writeDatasets(
            $targetPaths,
            $this->batchRows($source, $source->targetBatchId, $includeSensitive),
        );

        $paths = $this->datasetPaths($directory);
        $counts = [];
        foreach ([
            ApplicationResultExportDataset::Applications,
            ApplicationResultExportDataset::Documents,
            ApplicationResultExportDataset::Findings,
        ] as $dataset) {
            $counts[$dataset->value] = $this->store->write(
                $paths[$dataset->value],
                $this->store->rows($targetPaths[$dataset->value]),
            );
        }

        $baseReference = $this->batchSourceReference($source, 'base_batch');
        $targetReference = $this->batchSourceReference($source, 'target_batch');
        $changeStreams = [];
        $changeStreams[] = $this->comparator->compare(
            $this->store->rows($basePaths['applications']),
            $this->store->rows($targetPaths['applications']),
            'application',
            ['application_number', 'process_number'],
            $this->comparedFields(ApplicationResultExportDataset::Applications),
            $baseReference,
            $targetReference,
            $source->snapshotAt->toIso8601String(),
            $includeSensitive,
            $includeUnchanged,
        );
        $changeStreams[] = $this->comparator->compare(
            $this->store->rows($basePaths['documents']),
            $this->store->rows($targetPaths['documents']),
            'document',
            [
                'application_number',
                'required_document_code',
                'document_type_code',
                'target_type',
                'target_reference',
                'requirement_instance',
                'reference_period',
            ],
            $this->comparedFields(ApplicationResultExportDataset::Documents),
            $baseReference,
            $targetReference,
            $source->snapshotAt->toIso8601String(),
            $includeSensitive,
            $includeUnchanged,
        );
        $changeStreams[] = $this->comparator->compare(
            $this->store->rows($basePaths['findings']),
            $this->store->rows($targetPaths['findings']),
            'finding',
            ['application_number', 'finding_code'],
            $this->comparedFields(ApplicationResultExportDataset::Findings),
            $baseReference,
            $targetReference,
            $source->snapshotAt->toIso8601String(),
            $includeSensitive,
            $includeUnchanged,
        );
        $counts['changes'] = $this->store->write(
            $paths['changes'],
            $this->flatten($changeStreams),
        );

        $this->store->deleteDirectory($baseDirectory);
        $this->store->deleteDirectory($targetDirectory);

        foreach ($targetCounts as $dataset => $count) {
            if ($dataset !== 'changes' && ! isset($counts[$dataset])) {
                $counts[$dataset] = $count;
            }
        }

        return [$paths, $counts];
    }

    /**
     * @return array<string, iterable<array<string, mixed>>>
     */
    private function currentRows(
        ApplicationResultExportSourceData $source,
        bool $includeSensitive,
    ): array {
        return [
            'applications' => $this->currentApplicationRows($source, $includeSensitive),
            'documents' => $this->currentDocumentRows($source),
            'findings' => [],
            'changes' => [],
        ];
    }

    /**
     * @return array<string, iterable<array<string, mixed>>>
     */
    private function batchRows(
        ApplicationResultExportSourceData $source,
        int $batchId,
        bool $includeSensitive,
    ): array {
        $batch = ApplicationReviewBatch::query()->find($batchId);
        if (
            ! $batch instanceof ApplicationReviewBatch
            || $batch->municipality_id !== $source->municipalityId
            || $batch->contest_id !== $source->contestId
        ) {
            throw ValidationException::withMessages([
                'source' => 'O lote deixou de corresponder ao âmbito temporal autorizado.',
                'failure_code' => 'source_stale',
            ]);
        }

        $this->assertBatchIntegrity($batch);

        return [
            'applications' => $this->batchApplicationRows($source, $batch, $includeSensitive),
            'documents' => $this->batchDocumentRows($source, $batch),
            'findings' => $this->batchFindingRows($source, $batch),
            'changes' => [],
        ];
    }

    /**
     * @return array<string, iterable<array<string, mixed>>>
     */
    private function finalResultRows(
        ApplicationResultExportSourceData $source,
        bool $includeSensitive,
    ): array {
        return [
            'applications' => $this->officialResultRows($source, $includeSensitive),
            'documents' => [],
            'findings' => [],
            'changes' => [],
        ];
    }

    /** @return Generator<int, array<string, mixed>> */
    private function currentApplicationRows(
        ApplicationResultExportSourceData $source,
        bool $includeSensitive,
    ): Generator {
        foreach ($this->applicationChunks($source->contestId) as $applications) {
            foreach ($applications as $application) {
                $documents = $this->documentsForApplication($application);
                yield $this->project(
                    $this->currentApplicationRow(
                        $source,
                        $application,
                        $documents,
                        $includeSensitive,
                    ),
                    $source->mode,
                    ApplicationResultExportDataset::Applications,
                    $includeSensitive,
                );
            }
        }
    }

    /** @return Generator<int, array<string, mixed>> */
    private function currentDocumentRows(
        ApplicationResultExportSourceData $source,
    ): Generator {
        foreach ($this->applicationChunks($source->contestId) as $applications) {
            $rows = [];
            foreach ($applications as $application) {
                $process = $application->getRelationValue('administrativeProcess');
                foreach ($this->documentsForApplication($application) as $document) {
                    $rows[] = $this->project(
                        $this->currentDocumentRow(
                            $application,
                            $process instanceof AdministrativeProcess
                                ? $process->process_number
                                : null,
                            $document,
                        ),
                        $source->mode,
                        ApplicationResultExportDataset::Documents,
                        false,
                    );
                }
            }
            usort($rows, fn (array $left, array $right): int => $this->rowKey($left) <=> $this->rowKey($right));
            foreach ($rows as $row) {
                yield $row;
            }
        }
    }

    /**
     * @return Generator<int, Collection<int, Application>>
     */
    private function applicationChunks(int $contestId): Generator
    {
        $lastId = 0;
        while (true) {
            $applications = Application::query()
                ->where('contest_id', $contestId)
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit(self::CHUNK_SIZE)
                ->get([
                    'id',
                    'public_id',
                    'application_number',
                    'user_id',
                    'adhesion_registration_id',
                    'program_id',
                    'contest_id',
                    'status',
                    'submitted_at',
                    'updated_at',
                ]);
            if ($applications->isEmpty()) {
                break;
            }

            $applications->load([
                'user:id,name',
                'administrativeProcess:id,application_id,process_number,status,current_correction_request_id,updated_at',
                'administrativeProcess.latestDocumentalReview' => static function (Relation $relation): void {
                    $relation->getQuery()->select([
                        'application_reviews.id',
                        'application_reviews.administrative_process_id',
                        'application_reviews.application_id',
                        'application_reviews.status',
                        'application_reviews.result',
                        'application_reviews.updated_at',
                    ]);
                },
                'latestEligibilityCheck' => static function (Relation $relation): void {
                    $relation->getQuery()->select([
                        'eligibility_checks.id',
                        'eligibility_checks.application_id',
                        'eligibility_checks.status',
                        'eligibility_checks.result',
                        'eligibility_checks.executed_at',
                        'eligibility_checks.updated_at',
                    ]);
                },
                'latestApplicationScore' => static function (Relation $relation): void {
                    $relation->getQuery()->select([
                        'application_scores.id',
                        'application_scores.application_id',
                        'application_scores.status',
                        'application_scores.updated_at',
                    ]);
                },
                'latestApprovedAdministrativeDecision' => static function (Relation $relation): void {
                    $relation->getQuery()->select([
                        'administrative_decisions.id',
                        'administrative_decisions.application_id',
                        'administrative_decisions.status',
                        'administrative_decisions.decision_result',
                        'administrative_decisions.approved_at',
                        'administrative_decisions.updated_at',
                    ]);
                },
                'latestCorrectionRequest' => static function (Relation $relation): void {
                    $relation->getQuery()->select([
                        'correction_requests.id',
                        'correction_requests.application_id',
                        'correction_requests.status',
                        'correction_requests.response_deadline_at',
                        'correction_requests.submitted_at',
                        'correction_requests.revalidation_result',
                        'correction_requests.updated_at',
                    ]);
                },
            ]);
            $documents = $this->documentsForChunk($applications);
            $applications->each(function (Application $application) use ($documents): void {
                $application->setRelation(
                    'temporalDocuments',
                    $documents->filter(fn (DocumentSubmission $document): bool => (
                        $document->application_id === $application->id
                        || (
                            $document->adhesion_registration_id
                                === $application->adhesion_registration_id
                        )
                    ))->values(),
                );
            });

            $lastApplication = $applications->last();
            if (! $lastApplication instanceof Application) {
                throw new RuntimeException('A leitura temporal devolveu um lote vazio inesperado.');
            }
            $lastId = (int) $lastApplication->id;
            yield $applications;
        }
    }

    /**
     * @param  Collection<int, Application>  $applications
     * @return Collection<int, DocumentSubmission>
     */
    private function documentsForChunk(Collection $applications): Collection
    {
        $applicationIds = $applications->pluck('id')->all();
        $registrationIds = $applications->pluck('adhesion_registration_id')->filter()->all();

        return DocumentSubmission::query()
            ->where(function (Builder $query) use ($applicationIds, $registrationIds): void {
                $query->whereIn('application_id', $applicationIds);
                if ($registrationIds !== []) {
                    $query->orWhereIn('adhesion_registration_id', $registrationIds);
                }
            })
            ->with([
                'documentType:id,code',
                'requiredDocument:id,document_type_id,required_submissions',
                'currentVersion:id,document_submission_id,version_number,checksum',
            ])
            ->orderBy('id')
            ->get([
                'id',
                'application_id',
                'adhesion_registration_id',
                'household_id',
                'household_member_id',
                'income_record_id',
                'current_housing_situation_id',
                'contract_id',
                'document_type_id',
                'required_document_id',
                'current_version_id',
                'status',
                'requirement_instance',
                'reference_period',
                'submitted_at',
                'validated_at',
                'checksum',
            ]);
    }

    /** @return Collection<int, DocumentSubmission> */
    private function documentsForApplication(Application $application): Collection
    {
        $documents = $application->getRelationValue('temporalDocuments');

        return $documents instanceof Collection ? $documents : collect();
    }

    /**
     * @param  Collection<int, DocumentSubmission>  $documents
     * @return array<string, mixed>
     */
    private function currentApplicationRow(
        ApplicationResultExportSourceData $source,
        Application $application,
        Collection $documents,
        bool $includeSensitive,
    ): array {
        $process = $application->getRelationValue('administrativeProcess');
        $review = $process instanceof AdministrativeProcess
            ? $process->getRelationValue('latestDocumentalReview')
            : null;
        $eligibility = $application->getRelationValue('latestEligibilityCheck');
        $score = $application->getRelationValue('latestApplicationScore');
        $decision = $application->getRelationValue('latestApprovedAdministrativeDecision');
        $correction = $application->getRelationValue('latestCorrectionRequest');
        $valid = $documents->where('status', DocumentStatus::Validated)->count();
        $missing = $documents->where('status', DocumentStatus::Missing)->count();
        $invalid = $documents->whereIn('status', [
            DocumentStatus::Rejected,
            DocumentStatus::Expired,
        ])->count();

        return [
            ...$this->sourceColumns($source),
            'application_number' => $application->application_number,
            'process_number' => $process instanceof AdministrativeProcess
                ? $process->process_number
                : null,
            'candidate_name' => $includeSensitive
                ? $application->user->name
                : null,
            'submission_status_code' => $application->status->value,
            'submission_status_label' => $application->status->label(),
            'review_status_code' => $review instanceof ApplicationReview
                ? $review->status->value
                : null,
            'review_status_label' => $review instanceof ApplicationReview
                ? $review->status->label()
                : null,
            'review_result_code' => $review instanceof ApplicationReview
                ? $review->result?->value
                : null,
            'review_result_label' => $review instanceof ApplicationReview
                ? $review->result?->label()
                : null,
            'documents_required' => $documents->count(),
            'documents_valid' => $valid,
            'documents_missing' => $missing,
            'documents_invalid' => $invalid,
            'correction_required' => $correction instanceof CorrectionRequest,
            'correction_deadline' => $correction instanceof CorrectionRequest
                ? $this->dateTime($correction->response_deadline_at)
                : null,
            'correction_submitted_at' => $correction instanceof CorrectionRequest
                ? $this->dateTime($correction->submitted_at)
                : null,
            'revalidation_result_code' => $correction instanceof CorrectionRequest
                ? $correction->revalidation_result?->value
                : null,
            'eligibility_status_code' => $eligibility instanceof EligibilityCheck
                ? $eligibility->result?->value
                : null,
            'eligibility_status_label' => $eligibility instanceof EligibilityCheck
                ? $eligibility->result?->label()
                : null,
            'score_status_code' => $score instanceof ApplicationScore
                ? $score->status->value
                : null,
            'score_status_label' => $score instanceof ApplicationScore
                ? $score->status->label()
                : null,
            'final_administrative_status_code' => $decision instanceof AdministrativeDecision
                ? $decision->decision_result?->value
                : null,
            'final_administrative_status_label' => $decision instanceof AdministrativeDecision
                ? $decision->decision_result?->label()
                : null,
            'last_changed_at' => $this->latestDate([
                $application->updated_at,
                $process instanceof AdministrativeProcess ? $process->updated_at : null,
                $review instanceof ApplicationReview ? $review->updated_at : null,
                $eligibility instanceof EligibilityCheck ? $eligibility->updated_at : null,
                $score instanceof ApplicationScore ? $score->updated_at : null,
                $decision instanceof AdministrativeDecision ? $decision->updated_at : null,
                $correction instanceof CorrectionRequest ? $correction->updated_at : null,
            ]),
            'source_fingerprint' => null,
        ];
    }

    /** @return array<string, mixed> */
    private function currentDocumentRow(
        Application $application,
        ?string $processNumber,
        DocumentSubmission $document,
    ): array {
        $documentType = $document->getRelationValue('documentType');
        $required = $document->getRelationValue('requiredDocument');
        $version = $document->getRelationValue('currentVersion');
        [$targetType, $targetReference] = $this->currentDocumentTarget($document);

        return [
            'application_number' => $application->application_number,
            'process_number' => $processNumber,
            'required_document_code' => $required instanceof RequiredDocument
                ? 'required:'.$required->id
                : null,
            'document_type_code' => $documentType instanceof DocumentType
                ? $documentType->code
                : null,
            'target_type' => $targetType,
            'target_reference' => $targetReference,
            'requirement_instance' => (int) $document->requirement_instance,
            'required_submissions' => $required instanceof RequiredDocument
                ? $required->required_submissions
                : null,
            'reference_period' => $document->reference_period?->toDateString(),
            'document_status_code' => $document->status->value,
            'version_number' => $version instanceof DocumentVersion
                ? $version->version_number
                : null,
            'submitted_at' => $this->dateTime($document->submitted_at),
            'validated_at' => $this->dateTime($document->validated_at),
            'source_sha256' => $version instanceof DocumentVersion
                ? $version->checksum
                : $document->checksum,
            'carried_forward' => false,
            'source_batch_public_id' => null,
        ];
    }

    /**
     * @return Generator<int, array<string, mixed>>
     */
    private function batchApplicationRows(
        ApplicationResultExportSourceData $source,
        ApplicationReviewBatch $batch,
        bool $includeSensitive,
    ): Generator {
        $publicationAt = $this->publishedAt($source);
        foreach ($this->batchItems($batch) as $item) {
            yield $this->batchApplicationRow(
                $source,
                $batch,
                $item,
                $includeSensitive,
                $publicationAt,
            );
        }
    }

    /** @return array<string, mixed> */
    private function batchApplicationRow(
        ApplicationResultExportSourceData $source,
        ApplicationReviewBatch $batch,
        ApplicationReviewBatchItem $item,
        bool $includeSensitive,
        ?string $publishedAt = null,
    ): array {
        $payload = $item->snapshot_payload;
        $application = $this->array($payload['application'] ?? null);
        $review = $this->array($payload['review'] ?? null);
        $readiness = $this->array($payload['readiness'] ?? null);
        $correction = $this->array($payload['correction_request'] ?? null);
        $aggregate = $this->array($payload['aggregate_result'] ?? null);
        $submissionStatus = ApplicationStatus::tryFrom((string) ($application['status'] ?? ''));
        $reviewStatus = ApplicationReviewStatus::tryFrom((string) ($review['status'] ?? ''));
        $outcome = ApplicationReviewBatchOutcome::tryFrom((string) ($payload['outcome'] ?? ''));
        $revalidation = CorrectionRevalidationAggregateResult::tryFrom((string) ($aggregate['value'] ?? ''));

        return $this->project([
            ...$this->sourceColumns($source, $batch, $publishedAt),
            'application_number' => $item->application_number,
            'process_number' => $item->process_number,
            'candidate_name' => null,
            'submission_status_code' => $submissionStatus?->value,
            'submission_status_label' => $submissionStatus?->label(),
            'review_status_code' => $reviewStatus?->value,
            'review_status_label' => $reviewStatus?->label(),
            'review_result_code' => $outcome?->value,
            'review_result_label' => $outcome?->label(),
            'documents_required' => $this->integerOrNull($readiness['total_required'] ?? null),
            'documents_valid' => $this->integerOrNull($readiness['validated'] ?? null),
            'documents_missing' => $this->integerOrNull($readiness['missing'] ?? null),
            'documents_invalid' => $this->sumNullable(
                $readiness['rejected'] ?? null,
                $readiness['expired'] ?? null,
            ),
            'correction_required' => $outcome === ApplicationReviewBatchOutcome::CorrectionRequired,
            'correction_deadline' => null,
            'correction_submitted_at' => $correction['submitted_at'] ?? null,
            'revalidation_result_code' => $revalidation?->value,
            'eligibility_status_code' => null,
            'eligibility_status_label' => null,
            'score_status_code' => null,
            'score_status_label' => null,
            'final_administrative_status_code' => null,
            'final_administrative_status_label' => null,
            'last_changed_at' => $publishedAt ?? $batch->sealed_at->toIso8601String(),
            'source_fingerprint' => null,
        ], $source->mode, ApplicationResultExportDataset::Applications, $includeSensitive);
    }

    /** @return Generator<int, array<string, mixed>> */
    private function batchDocumentRows(
        ApplicationResultExportSourceData $source,
        ApplicationReviewBatch $batch,
    ): Generator {
        $rows = [];
        foreach ($this->batchItems($batch) as $item) {
            $payload = $item->snapshot_payload;
            $documents = $this->listOfArrays($payload['documents'] ?? []);
            $carriedKeys = collect($this->listOfArrays($payload['carried_forward_items'] ?? []))
                ->pluck('key')
                ->filter(fn (mixed $key): bool => is_string($key))
                ->all();

            foreach ($documents as $document) {
                $target = $this->array($document['target'] ?? null);
                [$targetType, $targetReference] = $this->snapshotTarget($target, $document);
                $classification = (string) ($document['classification'] ?? '');
                $key = (string) ($document['key'] ?? '');
                $rows[] = $this->project([
                    'application_number' => $item->application_number,
                    'process_number' => $item->process_number,
                    'required_document_code' => isset($document['required_document_id'])
                        ? 'required:'.(string) $document['required_document_id']
                        : null,
                    'document_type_code' => isset($document['document_type_id'])
                        ? 'type:'.(string) $document['document_type_id']
                        : null,
                    'target_type' => $targetType,
                    'target_reference' => $targetReference,
                    'requirement_instance' => (int) ($document['requirement_instance'] ?? 1),
                    'required_submissions' => null,
                    'reference_period' => $document['reference_period'] ?? null,
                    'document_status_code' => $document['status'] ?? $classification ?: null,
                    'version_number' => null,
                    'submitted_at' => $document['submitted_at'] ?? null,
                    'validated_at' => $document['validated_at'] ?? null,
                    'source_sha256' => $document['submitted_checksum']
                        ?? $document['checksum']
                        ?? null,
                    'carried_forward' => $classification === 'unchanged_valid'
                        || ($key !== '' && in_array($key, $carriedKeys, true)),
                    'source_batch_public_id' => $batch->public_id,
                ], $source->mode, ApplicationResultExportDataset::Documents, false);
            }
        }
        usort($rows, fn (array $left, array $right): int => $this->rowKey($left) <=> $this->rowKey($right));
        foreach ($rows as $row) {
            yield $row;
        }
    }

    /** @return Generator<int, array<string, mixed>> */
    private function batchFindingRows(
        ApplicationResultExportSourceData $source,
        ApplicationReviewBatch $batch,
    ): Generator {
        $rows = [];
        foreach ($this->batchItems($batch) as $item) {
            $payload = $item->snapshot_payload;
            foreach ($this->listOfArrays($payload['findings'] ?? []) as $finding) {
                $rows[] = $this->findingRow(
                    $source,
                    $batch,
                    $item,
                    $finding,
                    false,
                );
            }
            foreach ($this->listOfArrays($payload['decisions'] ?? []) as $decision) {
                $rows[] = $this->findingRow(
                    $source,
                    $batch,
                    $item,
                    $decision,
                    false,
                );
            }
            foreach ($this->listOfArrays($payload['carried_forward_items'] ?? []) as $carried) {
                $rows[] = $this->findingRow(
                    $source,
                    $batch,
                    $item,
                    $carried,
                    true,
                );
            }
        }
        usort($rows, fn (array $left, array $right): int => $this->rowKey($left) <=> $this->rowKey($right));
        foreach ($rows as $row) {
            yield $row;
        }
    }

    /**
     * @param  array<string, mixed>  $finding
     * @return array<string, mixed>
     */
    private function findingRow(
        ApplicationResultExportSourceData $source,
        ApplicationReviewBatch $batch,
        ApplicationReviewBatchItem $item,
        array $finding,
        bool $carriedForward,
    ): array {
        $status = (string) (
            $finding['finding_status']
            ?? $finding['result']
            ?? $finding['classification']
            ?? 'pending'
        );
        $code = trim((string) ($finding['key'] ?? ''));
        if ($code === '') {
            $code = hash('sha256', implode('|', [
                (string) ($finding['required_document_id'] ?? ''),
                (string) ($finding['requirement_instance'] ?? 1),
                (string) ($finding['target_type'] ?? ''),
                (string) ($finding['target_id'] ?? ''),
                $status,
            ]));
        }

        return $this->project([
            'application_number' => $item->application_number,
            'finding_code' => $code,
            'requirement_code' => isset($finding['required_document_id'])
                ? 'required:'.(string) $finding['required_document_id']
                : null,
            'finding_status_code' => $status,
            'finding_status_label' => $this->findingStatusLabel($status),
            'decision_code' => $finding['result'] ?? null,
            'carried_forward' => $carriedForward,
            'source_batch_public_id' => $batch->public_id,
            'decided_at' => $finding['reviewed_at'] ?? null,
            'resolved_at' => $this->array($item->snapshot_payload['correction_request'] ?? null)['resolved_at'] ?? null,
        ], $source->mode, ApplicationResultExportDataset::Findings, false);
    }

    /** @return Generator<int, array<string, mixed>> */
    private function officialResultRows(
        ApplicationResultExportSourceData $source,
        bool $includeSensitive,
    ): Generator {
        $lastId = 0;
        $validatedBatchIds = [];
        while (true) {
            $applications = Application::query()
                ->where('contest_id', $source->contestId)
                ->where('id', '>', $lastId)
                ->where('created_at', '<=', $source->snapshotAt)
                ->where(function (Builder $query) use ($source): void {
                    $query->whereNull('submitted_at')
                        ->orWhere('submitted_at', '<=', $source->snapshotAt);
                })
                ->orderBy('id')
                ->limit(self::CHUNK_SIZE)
                ->with('administrativeProcess:id,application_id,process_number')
                ->get([
                    'id',
                    'application_number',
                    'contest_id',
                    'submitted_at',
                ]);
            if ($applications->isEmpty()) {
                break;
            }

            $results = ApplicationReviewPublicationResult::query()
                ->where('municipality_id', $source->municipalityId)
                ->where('contest_id', $source->contestId)
                ->whereIn('application_id', $applications->pluck('id'))
                ->where('published_at', '<=', $source->snapshotAt)
                ->with([
                    'publication:id,public_id,municipality_id,contest_id,application_review_batch_id,cycle,sequence_number,status,item_count,source_snapshot_hash,publication_hash,published_at',
                    'batchItem:id,application_review_batch_id,application_id,process_number,application_number,outcome,snapshot_payload,snapshot_hash',
                    'batchItem.batch:id,public_id,municipality_id,contest_id,cycle,sequence_number,status,item_count,source_fingerprint,snapshot_hash,sealed_at',
                ])
                ->orderBy('application_id')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->get()
                ->unique('application_id')
                ->keyBy('application_id');

            foreach ($applications as $application) {
                $result = $results->get($application->id);
                if ($result instanceof ApplicationReviewPublicationResult) {
                    $item = $result->getRelationValue('batchItem');
                    $publication = $result->getRelationValue('publication');
                    if (! $item instanceof ApplicationReviewBatchItem) {
                        throw ValidationException::withMessages([
                            'source' => 'Um resultado oficial perdeu o snapshot autoritativo.',
                            'failure_code' => 'source_stale',
                        ]);
                    }
                    if (! hash_equals($item->snapshot_hash, $this->hasher->hash($item->snapshot_payload))) {
                        throw ValidationException::withMessages([
                            'source' => 'A integridade de um resultado oficial não pôde ser confirmada.',
                            'failure_code' => 'source_stale',
                        ]);
                    }
                    $batch = $item->getRelationValue('batch');
                    if (! $batch instanceof ApplicationReviewBatch) {
                        throw ValidationException::withMessages([
                            'source' => 'Um resultado oficial perdeu o lote autoritativo.',
                            'failure_code' => 'source_stale',
                        ]);
                    }
                    if (! isset($validatedBatchIds[$batch->id])) {
                        $this->assertBatchIntegrity($batch);
                        $validatedBatchIds[$batch->id] = true;
                    }
                    $this->assertOfficialResultIntegrity(
                        $source,
                        $result,
                        $publication,
                        $batch,
                        $item,
                    );
                    $rowSource = new ApplicationResultExportSourceData(
                        mode: $source->mode,
                        municipalityId: $source->municipalityId,
                        contestId: $source->contestId,
                        municipalityCode: $source->municipalityCode,
                        contestCode: $source->contestCode,
                        snapshotAt: $source->snapshotAt,
                        official: true,
                        sourceType: $source->sourceType,
                        sourceReferences: $source->sourceReferences,
                        batchId: (int) $batch->id,
                        phase: $batch->cycle->value,
                    );
                    $candidateRow = $this->batchApplicationRow(
                        $rowSource,
                        $batch,
                        $item,
                        $includeSensitive,
                        $result->published_at->toIso8601String(),
                    );
                    yield $candidateRow;
                } else {
                    $process = $application->getRelationValue('administrativeProcess');
                    yield $this->project([
                        ...$this->sourceColumns($source),
                        'application_number' => $application->application_number,
                        'process_number' => $process instanceof AdministrativeProcess
                            ? $process->process_number
                            : null,
                        'candidate_name' => null,
                        'submission_status_code' => null,
                        'submission_status_label' => null,
                        'review_status_code' => null,
                        'review_status_label' => null,
                        'review_result_code' => null,
                        'review_result_label' => 'Sem resultado oficial publicado',
                        'documents_required' => null,
                        'documents_valid' => null,
                        'documents_missing' => null,
                        'documents_invalid' => null,
                        'correction_required' => null,
                        'correction_deadline' => null,
                        'correction_submitted_at' => null,
                        'revalidation_result_code' => null,
                        'eligibility_status_code' => null,
                        'eligibility_status_label' => null,
                        'score_status_code' => null,
                        'score_status_label' => null,
                        'final_administrative_status_code' => null,
                        'final_administrative_status_label' => null,
                        'last_changed_at' => null,
                        'source_fingerprint' => null,
                    ], $source->mode, ApplicationResultExportDataset::Applications, $includeSensitive);
                }
            }

            $lastId = (int) $applications->last()->id;
        }
    }

    /**
     * @param  array<string, string>  $paths
     * @param  array<string, iterable<array<string, mixed>>>  $rows
     * @return array<string, int>
     *
     * @throws JsonException
     */
    private function writeDatasets(array $paths, array $rows): array
    {
        $counts = [];
        foreach (ApplicationResultExportDataset::cases() as $dataset) {
            $counts[$dataset->value] = $this->store->write(
                $paths[$dataset->value],
                $rows[$dataset->value] ?? [],
            );
        }

        return $counts;
    }

    /** @return array<string, string> */
    private function datasetPaths(string $directory): array
    {
        return [
            'applications' => $directory.'/applications.ndjson',
            'documents' => $directory.'/documents.ndjson',
            'findings' => $directory.'/findings.ndjson',
            'changes' => $directory.'/changes.ndjson',
        ];
    }

    /**
     * @param  array<string, string>  $paths
     * @return array<string, string>
     */
    private function checksums(array $paths): array
    {
        $checksums = [];
        foreach ($paths as $dataset => $path) {
            $checksums[$dataset] = $this->store->checksum($path);
        }
        ksort($checksums);

        return $checksums;
    }

    private function requiredBatchId(
        ApplicationResultExportSourceData $source,
    ): int {
        if ($source->batchId === null) {
            throw new RuntimeException('A fonte temporal não possui lote associado.');
        }

        return $source->batchId;
    }

    /**
     * @return Collection<int, ApplicationReviewBatchItem>
     *
     * @throws JsonException
     */
    private function batchItems(
        ApplicationReviewBatch $batch,
    ): Collection {
        return ApplicationReviewBatchItem::query()
            ->where('application_review_batch_id', $batch->id)
            ->orderBy('application_number')
            ->orderBy('process_number')
            ->orderBy('id')
            ->get();
    }

    /** @throws JsonException */
    private function assertBatchIntegrity(ApplicationReviewBatch $batch): void
    {
        $items = ApplicationReviewBatchItem::query()
            ->where('application_review_batch_id', $batch->id)
            ->orderBy('id')
            ->get([
                'id',
                'application_id',
                'snapshot_payload',
                'snapshot_hash',
            ]);
        if ($items->count() !== $batch->item_count || $items->isEmpty()) {
            throw ValidationException::withMessages([
                'source' => 'O lote não contém todos os itens selados esperados.',
                'failure_code' => 'source_stale',
            ]);
        }

        $batchItems = [];
        foreach ($items as $item) {
            if (! hash_equals($item->snapshot_hash, $this->hasher->hash($item->snapshot_payload))) {
                throw ValidationException::withMessages([
                    'source' => 'A integridade de um snapshot do lote não pôde ser confirmada.',
                    'failure_code' => 'source_stale',
                ]);
            }
            $batchItems[] = [
                'application_id' => (int) $item->application_id,
                'snapshot_hash' => $item->snapshot_hash,
                'payload' => $item->snapshot_payload,
            ];
        }
        $recomputed = $this->hasher->hash([
            'schema_version' => 1,
            'contest_id' => $batch->contest_id,
            'cycle' => $batch->cycle->value,
            'items' => $batchItems,
        ]);
        if (! hash_equals($batch->snapshot_hash, $recomputed)) {
            throw ValidationException::withMessages([
                'source' => 'O hash coletivo do lote não corresponde aos snapshots persistidos.',
                'failure_code' => 'source_stale',
            ]);
        }
    }

    private function assertOfficialResultIntegrity(
        ApplicationResultExportSourceData $source,
        ApplicationReviewPublicationResult $result,
        mixed $publication,
        ApplicationReviewBatch $batch,
        ApplicationReviewBatchItem $item,
    ): void {
        $valid = $publication instanceof ApplicationReviewPublication
            && (int) $publication->municipality_id === $source->municipalityId
            && (int) $publication->contest_id === $source->contestId
            && (int) $publication->application_review_batch_id === (int) $batch->id
            && hash_equals($publication->source_snapshot_hash, $batch->snapshot_hash)
            && (int) $result->municipality_id === $source->municipalityId
            && (int) $result->contest_id === $source->contestId
            && (int) $result->application_review_batch_item_id === (int) $item->id
            && (int) $result->application_id === (int) $item->application_id
            && (string) $result->application_number === (string) $item->application_number
            && hash_equals($result->source_snapshot_hash, $item->snapshot_hash)
            && hash_equals($result->result_hash, $this->hasher->hash($result->result_payload));

        if (! $valid) {
            throw ValidationException::withMessages([
                'source' => 'A integridade de um resultado oficial publicado não pôde ser confirmada.',
                'failure_code' => 'source_stale',
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function sourceColumns(
        ApplicationResultExportSourceData $source,
        ?ApplicationReviewBatch $batch = null,
        ?string $publishedAt = null,
    ): array {
        return [
            'municipality_code' => $source->municipalityCode,
            'contest_code' => $source->contestCode,
            'contest_public_id' => null,
            'phase_code' => $batch?->cycle->value ?? $source->phase,
            'batch_public_id' => $batch?->public_id,
            'batch_cycle' => $batch?->cycle->value,
            'batch_sequence' => $batch?->sequence_number,
            'snapshot_at' => $source->snapshotAt->toIso8601String(),
            'published_at' => $publishedAt,
        ];
    }

    private function publishedAt(
        ApplicationResultExportSourceData $source,
    ): ?string {
        $publication = $source->sourceReferences['publication'] ?? null;
        if (is_array($publication) && is_string($publication['published_at'] ?? null)) {
            return $publication['published_at'];
        }

        $target = $source->sourceReferences['target_publication'] ?? null;

        return is_array($target) && is_string($target['published_at'] ?? null)
            ? $target['published_at']
            : null;
    }

    private function batchSourceReference(
        ApplicationResultExportSourceData $source,
        string $key,
    ): string {
        $reference = $source->sourceReferences[$key] ?? null;

        return is_array($reference) && is_string($reference['public_id'] ?? null)
            ? $reference['public_id']
            : $key;
    }

    /** @return list<string> */
    private function comparedFields(
        ApplicationResultExportDataset $dataset,
    ): array {
        $ignored = [
            'municipality_code',
            'contest_code',
            'contest_public_id',
            'phase_code',
            'batch_public_id',
            'batch_cycle',
            'batch_sequence',
            'snapshot_at',
            'published_at',
            'source_fingerprint',
            'source_batch_public_id',
        ];

        $fields = [];
        foreach ($this->catalog->all() as $field) {
            if (
                in_array($dataset, $field->availableInDatasets, true)
                && ! in_array($field->code, $ignored, true)
            ) {
                $fields[] = $field->code;
            }
        }

        return $fields;
    }

    /**
     * @param  list<iterable<array<string, mixed>>>  $streams
     * @return Generator<int, array<string, mixed>>
     */
    private function flatten(array $streams): Generator
    {
        foreach ($streams as $stream) {
            foreach ($stream as $row) {
                yield $row;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function project(
        array $row,
        ApplicationResultExportMode $mode,
        ApplicationResultExportDataset $dataset,
        bool $includeSensitive,
    ): array {
        $projected = [];
        foreach ($this->catalog->forDataset($mode, $dataset, $includeSensitive) as $field) {
            $projected[$field->code] = $row[$field->code] ?? null;
        }

        return $projected;
    }

    /**
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    private function consistentRead(callable $callback): mixed
    {
        $connection = DB::connection();
        if ($connection->transactionLevel() > 0) {
            return $callback();
        }

        if (in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            $connection->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        }

        return $connection->transaction(
            static fn (): mixed => $callback(),
            attempts: 1,
        );
    }

    /** @return array{string|null, string|null} */
    private function currentDocumentTarget(DocumentSubmission $document): array
    {
        foreach ([
            'application' => $document->application_id,
            'adhesion_registration' => $document->adhesion_registration_id,
            'household' => $document->household_id,
            'household_member' => $document->household_member_id,
            'income_record' => $document->income_record_id,
            'current_housing_situation' => $document->current_housing_situation_id,
            'contract' => $document->contract_id,
        ] as $type => $id) {
            if ($id !== null) {
                return [$type, $type.':'.$id];
            }
        }

        return [null, null];
    }

    /**
     * @param  array<string, mixed>  $target
     * @param  array<string, mixed>  $document
     * @return array{string|null, string|null}
     */
    private function snapshotTarget(array $target, array $document): array
    {
        foreach ($target as $key => $id) {
            if ($id !== null && str_ends_with((string) $key, '_id')) {
                $type = substr((string) $key, 0, -3);

                return [$type, $type.':'.(string) $id];
            }
        }

        $type = is_string($document['target_type'] ?? null)
            ? $document['target_type']
            : null;
        $id = $document['target_id'] ?? null;

        return [$type, $type !== null && $id !== null ? $type.':'.(string) $id : null];
    }

    /** @param list<mixed> $values */
    private function latestDate(array $values): ?string
    {
        $dates = array_values(array_filter(
            $values,
            static fn (mixed $value): bool => $value instanceof CarbonInterface,
        ));
        usort(
            $dates,
            static fn (CarbonInterface $left, CarbonInterface $right): int => $left->getTimestamp() <=> $right->getTimestamp(),
        );
        $latest = $dates === [] ? null : end($dates);

        return $latest instanceof CarbonInterface
            ? CarbonImmutable::instance($latest)->utc()->toIso8601String()
            : null;
    }

    private function dateTime(mixed $value): ?string
    {
        return $value instanceof CarbonInterface
            ? CarbonImmutable::instance($value)->utc()->toIso8601String()
            : null;
    }

    /** @return array<string, mixed> */
    private function array(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /** @return list<array<string, mixed>> */
    private function listOfArrays(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $item): bool => is_array($item),
        ));
    }

    private function integerOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function sumNullable(mixed $left, mixed $right): ?int
    {
        if (! is_numeric($left) && ! is_numeric($right)) {
            return null;
        }

        return (int) $left + (int) $right;
    }

    /** @param array<string, mixed> $row */
    private function rowKey(array $row): string
    {
        return implode('|', array_map(
            static fn (mixed $value): string => $value === null ? '<null>' : (string) $value,
            array_values($row),
        ));
    }

    private function findingStatusLabel(string $status): string
    {
        return match ($status) {
            'missing' => 'Em falta',
            'invalid', 'rejected' => 'Não conforme',
            'expired' => 'Expirado',
            'accepted' => 'Aceite',
            'unchanged_valid' => 'Válido, transportado',
            'requires_manual_decision' => 'Requer decisão manual',
            default => 'Pendente de análise',
        };
    }
}
