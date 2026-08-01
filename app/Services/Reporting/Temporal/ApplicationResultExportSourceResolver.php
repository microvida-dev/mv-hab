<?php

namespace App\Services\Reporting\Temporal;

use App\Data\Reports\ApplicationResultExportSourceData;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\ApplicationReviewPublicationStatus;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewPublication;
use App\Models\Contest;
use App\Models\Municipality;
use App\Models\Program;
use App\Services\Support\CanonicalJsonHasher;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use JsonException;

final class ApplicationResultExportSourceResolver
{
    public function __construct(
        private readonly CanonicalJsonHasher $hasher,
    ) {}

    /**
     * @param  array<string, mixed>  $parameters
     *
     * @throws JsonException
     */
    public function resolve(
        Contest $contest,
        ApplicationResultExportMode $mode,
        array $parameters = [],
    ): ApplicationResultExportSourceData {
        $contest->loadMissing('program.municipality');
        $program = $contest->getRelationValue('program');
        $municipality = $program instanceof Program
            ? $program->getRelationValue('municipality')
            : null;

        if (! $program instanceof Program || ! $municipality instanceof Municipality) {
            throw $this->error(
                'source_not_found',
                'O concurso não possui uma origem municipal válida.',
            );
        }

        return match ($mode) {
            ApplicationResultExportMode::CurrentState => $this->currentState(
                $contest,
                $municipality,
            ),
            ApplicationResultExportMode::SealedBatch => $this->sealedBatch(
                $contest,
                $municipality,
                $parameters,
            ),
            ApplicationResultExportMode::PhaseSnapshot => $this->phaseSnapshot(
                $contest,
                $municipality,
                $parameters,
            ),
            ApplicationResultExportMode::DeltaBetweenBatches => $this->deltaBetweenBatches(
                $contest,
                $municipality,
                $parameters,
            ),
            ApplicationResultExportMode::DeltaSinceDatetime => $this->deltaSinceDatetime(
                $contest,
                $municipality,
                $parameters,
            ),
            ApplicationResultExportMode::FinalResult => $this->finalResult(
                $contest,
                $municipality,
                $parameters,
            ),
        };
    }

    private function currentState(
        Contest $contest,
        Municipality $municipality,
    ): ApplicationResultExportSourceData {
        $snapshotAt = CarbonImmutable::now('UTC');

        return $this->source(
            mode: ApplicationResultExportMode::CurrentState,
            contest: $contest,
            municipality: $municipality,
            snapshotAt: $snapshotAt,
            official: false,
            sourceType: 'consistent_operational_snapshot',
            references: [
                'captured_at' => $snapshotAt->toIso8601String(),
                'transaction_isolation' => 'repeatable_read',
            ],
            warnings: [
                'O estado operacional não constitui um resultado oficial publicado.',
            ],
        );
    }

    /** @param array<string, mixed> $parameters */
    private function sealedBatch(
        Contest $contest,
        Municipality $municipality,
        array $parameters,
    ): ApplicationResultExportSourceData {
        $batch = $this->batch(
            $contest,
            $municipality,
            $this->requiredString($parameters, 'batch_public_id'),
            sealedOnly: true,
        );

        return $this->sourceForBatch(
            ApplicationResultExportMode::SealedBatch,
            $contest,
            $municipality,
            $batch,
            official: false,
            sourceType: 'sealed_application_review_batch',
        );
    }

    /** @param array<string, mixed> $parameters */
    private function phaseSnapshot(
        Contest $contest,
        Municipality $municipality,
        array $parameters,
    ): ApplicationResultExportSourceData {
        $phase = ApplicationReviewBatchCycle::tryFrom(
            $this->requiredString($parameters, 'phase'),
        );
        if (! $phase instanceof ApplicationReviewBatchCycle) {
            throw $this->error(
                'source_not_found',
                'A fase indicada não possui snapshots de revisão suportados.',
            );
        }

        $asOf = $this->date($parameters['as_of'] ?? null, 'as_of')
            ?? CarbonImmutable::now('UTC');
        $publication = $this->publishedQuery($contest, $municipality)
            ->where('cycle', $phase->value)
            ->where('published_at', '<=', $asOf)
            ->orderByDesc('published_at')
            ->orderByDesc('sequence_number')
            ->orderByDesc('id')
            ->first();

        if (! $publication instanceof ApplicationReviewPublication) {
            throw $this->error(
                'source_not_found',
                'Não existe uma publicação autoritativa para a fase e instante indicados.',
            );
        }

        $publication->loadMissing('batch');
        $batch = $publication->getRelationValue('batch');
        if (! $batch instanceof ApplicationReviewBatch) {
            throw $this->error(
                'source_not_found',
                'A publicação não possui um lote autoritativo.',
            );
        }

        return $this->source(
            mode: ApplicationResultExportMode::PhaseSnapshot,
            contest: $contest,
            municipality: $municipality,
            snapshotAt: CarbonImmutable::instance($publication->published_at),
            official: true,
            sourceType: 'published_phase_snapshot',
            references: [
                'publication' => $this->publicationReference($publication),
                'batch' => $this->batchReference($batch),
                'as_of' => $asOf->toIso8601String(),
            ],
            batchId: (int) $batch->id,
            phase: $phase->value,
        );
    }

    /** @param array<string, mixed> $parameters */
    private function deltaBetweenBatches(
        Contest $contest,
        Municipality $municipality,
        array $parameters,
    ): ApplicationResultExportSourceData {
        $base = $this->batch(
            $contest,
            $municipality,
            $this->requiredString($parameters, 'base_batch_public_id'),
        );
        $target = $this->batch(
            $contest,
            $municipality,
            $this->requiredString($parameters, 'target_batch_public_id'),
        );
        $this->assertTemporalOrder($base, $target);

        return $this->source(
            mode: ApplicationResultExportMode::DeltaBetweenBatches,
            contest: $contest,
            municipality: $municipality,
            snapshotAt: CarbonImmutable::instance($target->sealed_at),
            official: false,
            sourceType: 'sealed_batch_delta',
            references: [
                'base_batch' => $this->batchReference($base),
                'target_batch' => $this->batchReference($target),
            ],
            baseBatchId: (int) $base->id,
            targetBatchId: (int) $target->id,
        );
    }

    /** @param array<string, mixed> $parameters */
    private function deltaSinceDatetime(
        Contest $contest,
        Municipality $municipality,
        array $parameters,
    ): ApplicationResultExportSourceData {
        $since = $this->date($parameters['since'] ?? null, 'since');
        if (! $since instanceof CarbonImmutable) {
            throw $this->error(
                'source_not_found',
                'É obrigatório indicar o instante de referência do delta.',
            );
        }

        $asOf = $this->date($parameters['as_of'] ?? null, 'as_of')
            ?? CarbonImmutable::now('UTC');
        if (! $since->lessThan($asOf)) {
            throw $this->error(
                'source_not_found',
                'O instante inicial deve ser anterior ao instante final.',
            );
        }

        $basePublication = $this->publishedQuery($contest, $municipality)
            ->where('published_at', '<=', $since)
            ->orderByDesc('published_at')
            ->orderByDesc('sequence_number')
            ->orderByDesc('id')
            ->first();
        $targetPublication = $this->publishedQuery($contest, $municipality)
            ->where('published_at', '>', $since)
            ->where('published_at', '<=', $asOf)
            ->orderByDesc('published_at')
            ->orderByDesc('sequence_number')
            ->orderByDesc('id')
            ->first();

        if (
            ! $basePublication instanceof ApplicationReviewPublication
            || ! $targetPublication instanceof ApplicationReviewPublication
        ) {
            throw $this->error(
                'source_not_found',
                'Não existe baseline e alvo históricos autoritativos para reconstruir o delta.',
            );
        }

        $basePublication->loadMissing('batch');
        $targetPublication->loadMissing('batch');
        $base = $basePublication->getRelationValue('batch');
        $target = $targetPublication->getRelationValue('batch');
        if (
            ! $base instanceof ApplicationReviewBatch
            || ! $target instanceof ApplicationReviewBatch
        ) {
            throw $this->error(
                'source_not_found',
                'Uma publicação histórica não possui lote autoritativo.',
            );
        }
        $this->assertTemporalOrder($base, $target);

        return $this->source(
            mode: ApplicationResultExportMode::DeltaSinceDatetime,
            contest: $contest,
            municipality: $municipality,
            snapshotAt: CarbonImmutable::instance($targetPublication->published_at),
            official: true,
            sourceType: 'published_snapshot_delta',
            references: [
                'base_publication' => $this->publicationReference($basePublication),
                'target_publication' => $this->publicationReference($targetPublication),
                'base_batch' => $this->batchReference($base),
                'target_batch' => $this->batchReference($target),
                'as_of' => $asOf->toIso8601String(),
            ],
            baseBatchId: (int) $base->id,
            targetBatchId: (int) $target->id,
            since: $since,
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     *
     * @throws JsonException
     */
    private function finalResult(
        Contest $contest,
        Municipality $municipality,
        array $parameters,
    ): ApplicationResultExportSourceData {
        $asOf = $this->date($parameters['as_of'] ?? null, 'as_of')
            ?? CarbonImmutable::now('UTC');
        $publications = $this->publishedQuery($contest, $municipality)
            ->where('published_at', '<=', $asOf)
            ->orderBy('published_at')
            ->orderBy('sequence_number')
            ->orderBy('id')
            ->get([
                'id',
                'public_id',
                'application_review_batch_id',
                'cycle',
                'sequence_number',
                'source_snapshot_hash',
                'publication_hash',
                'published_at',
            ]);
        $references = $publications
            ->map(fn (ApplicationReviewPublication $publication): array => $this
                ->publicationReference($publication))
            ->values()
            ->all();

        return $this->source(
            mode: ApplicationResultExportMode::FinalResult,
            contest: $contest,
            municipality: $municipality,
            snapshotAt: $asOf,
            official: true,
            sourceType: 'latest_official_publication_result',
            references: [
                'as_of' => $asOf->toIso8601String(),
                'publication_count' => count($references),
                'publication_fingerprint' => $this->hasher->hash($references),
                'publications' => $references,
            ],
            warnings: $references === []
                ? ['Não existiam resultados oficiais publicados no instante indicado.']
                : [],
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function requiredString(array $parameters, string $key): string
    {
        $value = trim((string) ($parameters[$key] ?? ''));
        if ($value === '') {
            throw $this->error(
                'source_not_found',
                'A referência temporal obrigatória não foi indicada.',
            );
        }

        return $value;
    }

    private function batch(
        Contest $contest,
        Municipality $municipality,
        string $publicId,
        bool $sealedOnly = false,
    ): ApplicationReviewBatch {
        $batch = ApplicationReviewBatch::query()
            ->where('municipality_id', $municipality->id)
            ->where('contest_id', $contest->id)
            ->where('public_id', $publicId)
            ->first();

        if (
            ! $batch instanceof ApplicationReviewBatch
            || ($sealedOnly
                && $batch->status !== ApplicationReviewBatchStatus::Sealed)
        ) {
            throw $this->error(
                'source_not_found',
                'O lote temporal indicado não está disponível.',
            );
        }

        return $batch;
    }

    private function assertTemporalOrder(
        ApplicationReviewBatch $base,
        ApplicationReviewBatch $target,
    ): void {
        if (
            $base->id === $target->id
            || ! $base->sealed_at->lessThan($target->sealed_at)
        ) {
            throw $this->error(
                'source_not_found',
                'Os lotes não possuem uma ordem temporal válida.',
            );
        }
    }

    /** @return Builder<ApplicationReviewPublication> */
    private function publishedQuery(
        Contest $contest,
        Municipality $municipality,
    ): Builder {
        return ApplicationReviewPublication::query()
            ->where('municipality_id', $municipality->id)
            ->where('contest_id', $contest->id)
            ->where('status', ApplicationReviewPublicationStatus::Published->value);
    }

    private function sourceForBatch(
        ApplicationResultExportMode $mode,
        Contest $contest,
        Municipality $municipality,
        ApplicationReviewBatch $batch,
        bool $official,
        string $sourceType,
    ): ApplicationResultExportSourceData {
        return $this->source(
            mode: $mode,
            contest: $contest,
            municipality: $municipality,
            snapshotAt: CarbonImmutable::instance($batch->sealed_at),
            official: $official,
            sourceType: $sourceType,
            references: ['batch' => $this->batchReference($batch)],
            batchId: (int) $batch->id,
            phase: $batch->cycle->value,
        );
    }

    /** @return array<string, mixed> */
    private function batchReference(ApplicationReviewBatch $batch): array
    {
        return [
            'public_id' => $batch->public_id,
            'cycle' => $batch->cycle->value,
            'sequence_number' => $batch->sequence_number,
            'status' => $batch->status->value,
            'source_fingerprint' => $batch->source_fingerprint,
            'snapshot_hash' => $batch->snapshot_hash,
            'sealed_at' => $batch->sealed_at->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function publicationReference(
        ApplicationReviewPublication $publication,
    ): array {
        return [
            'public_id' => $publication->public_id,
            'cycle' => $publication->cycle->value,
            'sequence_number' => $publication->sequence_number,
            'source_snapshot_hash' => $publication->source_snapshot_hash,
            'publication_hash' => $publication->publication_hash,
            'published_at' => $publication->published_at->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $references
     * @param  list<string>  $warnings
     */
    private function source(
        ApplicationResultExportMode $mode,
        Contest $contest,
        Municipality $municipality,
        CarbonImmutable $snapshotAt,
        bool $official,
        string $sourceType,
        array $references,
        ?int $batchId = null,
        ?int $baseBatchId = null,
        ?int $targetBatchId = null,
        ?string $phase = null,
        ?CarbonImmutable $since = null,
        array $warnings = [],
    ): ApplicationResultExportSourceData {
        return new ApplicationResultExportSourceData(
            mode: $mode,
            municipalityId: (int) $municipality->id,
            contestId: (int) $contest->id,
            municipalityCode: (string) $municipality->code,
            contestCode: (string) $contest->code,
            snapshotAt: $snapshotAt->utc(),
            official: $official,
            sourceType: $sourceType,
            sourceReferences: $references,
            batchId: $batchId,
            baseBatchId: $baseBatchId,
            targetBatchId: $targetBatchId,
            phase: $phase,
            since: $since?->utc(),
            warnings: $warnings,
        );
    }

    private function date(mixed $value, string $field): ?CarbonImmutable
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value, 'UTC')->utc();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $field => 'O instante temporal indicado não é válido.',
            ]);
        }
    }

    private function error(string $code, string $message): ValidationException
    {
        return ValidationException::withMessages([
            'source' => $message,
            'failure_code' => $code,
        ]);
    }
}
