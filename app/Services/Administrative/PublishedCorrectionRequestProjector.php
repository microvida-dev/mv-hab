<?php

namespace App\Services\Administrative;

use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewPublicationStatus;
use App\Enums\ContestDeadlineType;
use App\Enums\CorrectionRequestItemStatus;
use App\Enums\CorrectionRequestStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReviewBatchItem;
use App\Models\ApplicationReviewPublication;
use App\Models\ApplicationReviewPublicationResult;
use App\Models\Contest;
use App\Models\CorrectionRequest;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Contests\ContestApplicationPhaseService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Platform\PlatformOperatorScopeService;
use App\Services\Support\CanonicalJsonHasher;
use App\Support\AuditEvents;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishedCorrectionRequestProjector
{
    public function __construct(
        private readonly PublishedCorrectionFindingMapper $findings,
        private readonly CorrectionRequestNumberService $numbers,
        private readonly ContestApplicationPhaseService $phases,
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly PlatformOperatorScopeService $platformScope,
        private readonly CanonicalJsonHasher $hasher,
        private readonly AuditLogger $audit,
    ) {}

    public function project(
        ApplicationReviewPublicationResult $result,
        User $actor,
    ): CorrectionRequest {
        return DB::transaction(function () use ($result, $actor): CorrectionRequest {
            $lockedResult = ApplicationReviewPublicationResult::query()
                ->whereKey($result->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedResult->load([
                'publication',
                'batchItem',
                'administrativeProcess',
                'application.contest.program',
                'user',
            ]);

            $this->assertProjectable($lockedResult, $actor);

            $existing = CorrectionRequest::query()
                ->where(
                    'application_review_publication_result_id',
                    $lockedResult->id,
                )
                ->with(['items', 'publicationResult'])
                ->lockForUpdate()
                ->first();

            if ($existing instanceof CorrectionRequest) {
                return $existing;
            }

            $publication = $this->requiredPublication($lockedResult);
            $batchItem = $this->requiredBatchItem($lockedResult);
            $process = $this->requiredProcess($lockedResult);
            $application = $this->requiredApplication($lockedResult);
            $contest = $this->requiredContest($application);
            $program = $this->requiredProgram($contest);
            $deadline = $this->phases->deadline(
                $contest,
                ContestDeadlineType::Corrections,
            );

            if ($deadline === null || $deadline->starts_at === null) {
                throw ValidationException::withMessages([
                    'correction_request' => 'O concurso não possui uma fase de aperfeiçoamento configurada.',
                ]);
            }

            if ($deadline->ends_at->lessThan($publication->published_at)) {
                throw ValidationException::withMessages([
                    'correction_request' => 'A fase de aperfeiçoamento termina antes da publicação do resultado.',
                ]);
            }

            $items = $this->findings->map($batchItem->snapshot_payload);
            $request = new CorrectionRequest([
                'subject' => 'Pedido de aperfeiçoamento da candidatura',
                'message' => (string) ($lockedResult->result_payload['message'] ?? ''),
                'legal_basis' => $publication->reason,
                'instructions' => 'Consulte cada elemento assinalado e apresente apenas as correções solicitadas dentro do prazo indicado.',
                'response_deadline_at' => $deadline->ends_at,
                'candidate_visible' => true,
                'internal_notes' => null,
            ]);
            $request->forceFill([
                'application_review_publication_result_id' => $lockedResult->id,
                'source_snapshot_hash' => $lockedResult->source_snapshot_hash,
                'administrative_process_id' => $process->id,
                'application_id' => $application->id,
                'user_id' => $lockedResult->user_id,
                'request_number' => $this->numbers->next(
                    (int) $program->municipality_id,
                    (int) $publication->published_at->format('Y'),
                ),
                'status' => CorrectionRequestStatus::Notified,
                'issued_by' => $actor->id,
                'issued_at' => $publication->published_at,
                'notified_at' => $publication->published_at,
                'opened_at' => $deadline->starts_at,
            ]);
            $request->save();

            foreach ($items as $item) {
                $requestItem = $request->items()->make($item);
                $requestItem->forceFill([
                    'status' => CorrectionRequestItemStatus::Pending,
                ])->save();
            }

            $process->forceFill([
                'current_correction_request_id' => $request->id,
            ])->save();

            $this->audit->record(
                event: AuditEvents::CREATE,
                auditable: $request,
                module: 'administrative_processes',
                action: 'published_correction_request_projected',
                description: 'Pedido de aperfeiçoamento projetado a partir de um resultado publicado.',
                newValues: [
                    'status' => CorrectionRequestStatus::Notified->value,
                    'response_deadline_at' => $deadline->ends_at->toIso8601String(),
                ],
                metadata: [
                    'actor_id' => $actor->id,
                    'correction_request_id' => $request->id,
                    'publication_result_id' => $lockedResult->id,
                    'batch_item_id' => $batchItem->id,
                    'contest_id' => $contest->id,
                    'application_id' => $application->id,
                    'municipality_id' => $program->municipality_id,
                    'source_snapshot_hash' => $lockedResult->source_snapshot_hash,
                    'operation_id' => hash(
                        'sha256',
                        'published-correction-request:'.$lockedResult->id,
                    ),
                ],
            );

            return $request->load(['items', 'publicationResult']);
        }, 3);
    }

    /**
     * @return Collection<int, CorrectionRequest>
     */
    public function projectPublication(
        ApplicationReviewPublication $publication,
        User $actor,
    ): Collection {
        return $publication->results()
            ->where('outcome', ApplicationReviewBatchOutcome::CorrectionRequired)
            ->orderBy('id')
            ->get()
            ->map(
                fn (ApplicationReviewPublicationResult $result): CorrectionRequest => $this->project(
                    $result,
                    $actor,
                ),
            );
    }

    private function assertProjectable(
        ApplicationReviewPublicationResult $result,
        User $actor,
    ): void {
        $publication = $this->requiredPublication($result);
        $batchItem = $this->requiredBatchItem($result);
        $process = $this->requiredProcess($result);
        $application = $this->requiredApplication($result);
        $contest = $this->requiredContest($application);
        $program = $this->requiredProgram($contest);

        $hasScope = $this->platformScope->hasGlobalScope($actor)
            || (
                $actor->municipality_id !== null
                && (int) $actor->municipality_id === (int) $program->municipality_id
                && $this->municipalScope->ownsApplication($actor, $application)
            );

        if (! $hasScope) {
            abort(403);
        }

        $publicationStatus = ApplicationReviewPublicationStatus::tryFrom(
            (string) $publication->getRawOriginal('status'),
        );

        if ($publicationStatus !== ApplicationReviewPublicationStatus::Published
            || $publication->published_at->isFuture()
            || $result->published_at->isFuture()) {
            throw ValidationException::withMessages([
                'correction_request' => 'O resultado ainda não se encontra publicado.',
            ]);
        }

        if ($result->outcome !== ApplicationReviewBatchOutcome::CorrectionRequired
            || ($result->result_payload['next_action'] ?? null) !== 'await_correction_request') {
            throw ValidationException::withMessages([
                'correction_request' => 'O resultado publicado não admite um pedido de aperfeiçoamento.',
            ]);
        }

        $integrityIsValid = hash_equals(
            $result->source_snapshot_hash,
            $batchItem->snapshot_hash,
        ) && hash_equals(
            $batchItem->snapshot_hash,
            $this->hasher->hash($batchItem->snapshot_payload),
        ) && hash_equals(
            (string) ($result->result_payload['source_snapshot_hash'] ?? ''),
            $result->source_snapshot_hash,
        );

        if (! $integrityIsValid) {
            throw ValidationException::withMessages([
                'correction_request' => 'A integridade do snapshot publicado não pôde ser confirmada.',
            ]);
        }

        $consistent = (int) $result->application_review_batch_item_id === (int) $batchItem->id
            && (int) $result->administrative_process_id === (int) $process->id
            && (int) $result->application_id === (int) $application->id
            && (int) $result->user_id === (int) $application->user_id
            && (int) $result->contest_id === (int) $contest->id
            && (int) $result->municipality_id === (int) $program->municipality_id;

        if (! $consistent) {
            throw ValidationException::withMessages([
                'correction_request' => 'O resultado publicado não possui um contexto processual e municipal coerente.',
            ]);
        }
    }

    private function requiredPublication(
        ApplicationReviewPublicationResult $result,
    ): ApplicationReviewPublication {
        $publication = $result->getRelationValue('publication');

        if (! $publication instanceof ApplicationReviewPublication) {
            throw ValidationException::withMessages([
                'correction_request' => 'Resultado sem publicação associada.',
            ]);
        }

        return $publication;
    }

    private function requiredBatchItem(
        ApplicationReviewPublicationResult $result,
    ): ApplicationReviewBatchItem {
        $item = $result->getRelationValue('batchItem');

        if (! $item instanceof ApplicationReviewBatchItem) {
            throw ValidationException::withMessages([
                'correction_request' => 'Resultado sem item de lote associado.',
            ]);
        }

        return $item;
    }

    private function requiredProcess(
        ApplicationReviewPublicationResult $result,
    ): AdministrativeProcess {
        $process = $result->getRelationValue('administrativeProcess');

        if (! $process instanceof AdministrativeProcess) {
            throw ValidationException::withMessages([
                'correction_request' => 'Resultado sem processo administrativo associado.',
            ]);
        }

        return $process;
    }

    private function requiredApplication(
        ApplicationReviewPublicationResult $result,
    ): Application {
        $application = $result->getRelationValue('application');

        if (! $application instanceof Application) {
            throw ValidationException::withMessages([
                'correction_request' => 'Resultado sem candidatura associada.',
            ]);
        }

        return $application;
    }

    private function requiredContest(Application $application): Contest
    {
        $contest = $application->getRelationValue('contest');

        if (! $contest instanceof Contest) {
            throw ValidationException::withMessages([
                'correction_request' => 'Candidatura sem concurso associado.',
            ]);
        }

        return $contest;
    }

    private function requiredProgram(Contest $contest): Program
    {
        $program = $contest->getRelationValue('program');

        if (! $program instanceof Program) {
            throw ValidationException::withMessages([
                'correction_request' => 'Concurso sem programa municipal associado.',
            ]);
        }

        return $program;
    }
}
