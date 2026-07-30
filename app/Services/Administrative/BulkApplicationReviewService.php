<?php

namespace App\Services\Administrative;

use App\Enums\BulkApplicationReviewAction;
use App\Enums\DocumentStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\Contest;
use App\Models\DocumentSubmission;
use App\Models\User;
use App\Services\Documents\DocumentReviewService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use JsonException;

class BulkApplicationReviewService
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly AdministrativeProcessService $processService,
        private readonly DocumentReviewService $documentReviewService,
        private readonly ProgressiveApplicationReviewService $progressiveReviewService,
        private readonly ApplicationReviewReadinessService $readinessService,
    ) {}

    /**
     * @param  array{
     *     action: BulkApplicationReviewAction,
     *     process_ids: list<int>,
     *     document_ids: list<int>,
     *     assigned_to: int|null,
     *     reason: string|null,
     *     internal_notes: string|null,
     *     preview_token: string|null
     * }  $payload
     * @return array{
     *     action: BulkApplicationReviewAction,
     *     action_label: string,
     *     process_ids: list<int>,
     *     document_ids: list<int>,
     *     assigned_to: int|null,
     *     assignee_name: string|null,
     *     reason: string|null,
     *     internal_notes: string|null,
     *     processes: Collection<int, AdministrativeProcess>,
     *     documents: Collection<int, DocumentSubmission>,
     *     blockers: list<string>,
     *     token: string
     * }
     */
    public function preview(
        Contest $contest,
        User $actor,
        array $payload,
    ): array {
        return $this->buildPreview(
            $contest,
            $actor,
            $payload,
            false,
        );
    }

    /**
     * @param  array{
     *     action: BulkApplicationReviewAction,
     *     process_ids: list<int>,
     *     document_ids: list<int>,
     *     assigned_to: int|null,
     *     reason: string|null,
     *     internal_notes: string|null,
     *     preview_token: string|null
     * }  $payload
     * @return array{processes: int, documents: int, action: string}
     */
    public function apply(
        Contest $contest,
        User $actor,
        array $payload,
    ): array {
        return DB::transaction(function () use (
            $contest,
            $actor,
            $payload,
        ): array {
            $preview = $this->buildPreview(
                $contest,
                $actor,
                $payload,
                true,
            );

            if (! is_string($payload['preview_token'])
                || ! hash_equals(
                    $preview['token'],
                    $payload['preview_token'],
                )) {
                throw ValidationException::withMessages([
                    'preview_token' => 'A seleção foi alterada. Gere uma nova pré-visualização.',
                ]);
            }

            if ($preview['blockers'] !== []) {
                throw ValidationException::withMessages([
                    'process_ids' => $preview['blockers'],
                ]);
            }

            $action = $payload['action'];

            match ($action) {
                BulkApplicationReviewAction::AssignAnalyst => $this->assignAnalyst(
                    $preview['processes'],
                    $payload['assigned_to'],
                    $actor,
                ),
                BulkApplicationReviewAction::MarkDocumentsUnderReview,
                BulkApplicationReviewAction::ValidateDocuments,
                BulkApplicationReviewAction::RejectDocuments => $this->transitionDocuments(
                    $preview['documents'],
                    $actor,
                    $action,
                    $payload['reason'],
                    $payload['internal_notes'],
                ),
                BulkApplicationReviewAction::MarkReadyForClosure => $this->markReady(
                    $preview['processes'],
                    $actor,
                ),
                BulkApplicationReviewAction::ReopenReview => $this->reopen(
                    $preview['processes'],
                    $actor,
                    $payload['reason'],
                ),
            };

            return [
                'processes' => $preview['processes']->count(),
                'documents' => $preview['documents']->count(),
                'action' => $action->label(),
            ];
        }, 3);
    }

    /**
     * @param  array{
     *     action: BulkApplicationReviewAction,
     *     process_ids: list<int>,
     *     document_ids: list<int>,
     *     assigned_to: int|null,
     *     reason: string|null,
     *     internal_notes: string|null,
     *     preview_token: string|null
     * }  $payload
     * @return array{
     *     action: BulkApplicationReviewAction,
     *     action_label: string,
     *     process_ids: list<int>,
     *     document_ids: list<int>,
     *     assigned_to: int|null,
     *     assignee_name: string|null,
     *     reason: string|null,
     *     internal_notes: string|null,
     *     processes: Collection<int, AdministrativeProcess>,
     *     documents: Collection<int, DocumentSubmission>,
     *     blockers: list<string>,
     *     token: string
     * }
     */
    private function buildPreview(
        Contest $contest,
        User $actor,
        array $payload,
        bool $lockForUpdate,
    ): array {
        $processes = $this->resolveProcesses(
            $contest,
            $actor,
            $payload['process_ids'],
            $lockForUpdate,
        );
        $documents = $this->resolveDocuments(
            $actor,
            $processes,
            $payload['document_ids'],
            $lockForUpdate,
        );
        $readinessDocuments = $this->resolveReadinessDocuments(
            $actor,
            $payload['action'],
            $processes,
            $lockForUpdate,
        );
        $assignee = $this->resolveAssignee(
            $actor,
            $payload['action'],
            $payload['assigned_to'],
            $lockForUpdate,
        );

        $this->authorizeSelection(
            $actor,
            $payload['action'],
            $processes,
            $documents,
        );

        $blockers = $this->blockers(
            $payload['action'],
            $processes,
            $documents,
        );

        return [
            'action' => $payload['action'],
            'action_label' => $payload['action']->label(),
            'process_ids' => $payload['process_ids'],
            'document_ids' => $payload['document_ids'],
            'assigned_to' => $payload['assigned_to'],
            'assignee_name' => $assignee?->name,
            'reason' => $payload['reason'],
            'internal_notes' => $payload['internal_notes'],
            'processes' => $processes,
            'documents' => $documents,
            'blockers' => $blockers,
            'token' => $this->token(
                $contest,
                $actor,
                $payload,
                $processes,
                $documents,
                $readinessDocuments,
                $assignee,
            ),
        ];
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, AdministrativeProcess>
     */
    private function resolveProcesses(
        Contest $contest,
        User $actor,
        array $ids,
        bool $lockForUpdate,
    ): Collection {
        $query = $this->municipalScope
            ->administrativeProcesses(
                AdministrativeProcess::query(),
                $actor,
            )
            ->where('contest_id', $contest->id)
            ->whereIn('id', $ids)
            ->with([
                'application.adhesionRegistration',
                'candidate',
                'assignedTo',
                'latestDocumentalReview',
            ])
            ->orderBy('id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $processes = $query->get();

        if ($processes->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'process_ids' => 'A seleção contém processos indisponíveis neste concurso ou Município.',
            ]);
        }

        return $processes;
    }

    /**
     * @param  Collection<int, AdministrativeProcess>  $processes
     * @param  list<int>  $ids
     * @return Collection<int, DocumentSubmission>
     */
    private function resolveDocuments(
        User $actor,
        Collection $processes,
        array $ids,
        bool $lockForUpdate,
    ): Collection {
        if ($ids === []) {
            /** @var Collection<int, DocumentSubmission> $empty */
            $empty = collect();

            return $empty;
        }

        $applicationIds = [];

        foreach ($processes as $process) {
            $applicationIds[] = $process->application_id;
        }

        $query = $this->municipalScope
            ->documentSubmissions(
                DocumentSubmission::query(),
                $actor,
            )
            ->whereIn('application_id', array_values(array_unique(
                $applicationIds,
            )))
            ->whereIn('id', $ids)
            ->with([
                'application.administrativeProcess',
                'documentType',
                'requiredDocument',
                'currentVersion',
            ])
            ->orderBy('id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $documents = $query->get();

        if ($documents->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'document_ids' => 'A seleção contém documentos fora dos processos autorizados.',
            ]);
        }

        return $documents;
    }

    /**
     * Lock every document that can influence readiness for the selected
     * applications. This closes the race between preview validation and the
     * technical ready-for-closure transition.
     *
     * @param  Collection<int, AdministrativeProcess>  $processes
     * @return Collection<int, DocumentSubmission>
     */
    private function resolveReadinessDocuments(
        User $actor,
        BulkApplicationReviewAction $action,
        Collection $processes,
        bool $lockForUpdate,
    ): Collection {
        if ($action !== BulkApplicationReviewAction::MarkReadyForClosure) {
            /** @var Collection<int, DocumentSubmission> $empty */
            $empty = collect();

            return $empty;
        }

        $applicationIds = [];
        $registrationIds = [];

        foreach ($processes as $process) {
            $applicationIds[] = $process->application_id;
            $application = $process->application;

            if ($application instanceof Application) {
                $registrationIds[] = $application
                    ->adhesion_registration_id;
            }
        }

        $query = $this->municipalScope
            ->documentSubmissions(
                DocumentSubmission::query(),
                $actor,
            )
            ->where(function (Builder $documents) use (
                $applicationIds,
                $registrationIds,
            ): void {
                $documents->whereIn(
                    'application_id',
                    array_values(array_unique($applicationIds)),
                );

                if ($registrationIds !== []) {
                    $documents->orWhereIn(
                        'adhesion_registration_id',
                        array_values(array_unique($registrationIds)),
                    );
                }
            })
            ->orderBy('id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    private function resolveAssignee(
        User $actor,
        BulkApplicationReviewAction $action,
        ?int $assigneeId,
        bool $lockForUpdate,
    ): ?User {
        if (! $action->requiresAssignee()) {
            return null;
        }

        $query = $this->municipalScope
            ->users(User::query(), $actor)
            ->whereKey($assigneeId)
            ->where('status', 'active');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $assignee = $query->first();

        if (! $assignee instanceof User
            || ! $this->isEligibleAnalyst($assignee)) {
            throw ValidationException::withMessages([
                'assigned_to' => 'O analista selecionado não está disponível para atribuição.',
            ]);
        }

        return $assignee;
    }

    private function isEligibleAnalyst(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $user->hasPermissionTo(
                'administrative_processes',
                'view',
            )
            && $user->hasPermissionTo('documents', 'view');
    }

    /**
     * @param  Collection<int, AdministrativeProcess>  $processes
     * @param  Collection<int, DocumentSubmission>  $documents
     */
    private function authorizeSelection(
        User $actor,
        BulkApplicationReviewAction $action,
        Collection $processes,
        Collection $documents,
    ): void {
        $processAbility = $action === BulkApplicationReviewAction::AssignAnalyst
            ? 'assignBackoffice'
            : 'update';

        foreach ($processes as $process) {
            Gate::forUser($actor)->authorize(
                $processAbility,
                $process,
            );
        }

        if ($action !== BulkApplicationReviewAction::AssignAnalyst) {
            Gate::forUser($actor)->authorize(
                'viewAnyBackoffice',
                DocumentSubmission::class,
            );
        }

        if (! $action->isDocumentAction()) {
            return;
        }

        $documentAbility = $action === BulkApplicationReviewAction::RejectDocuments
            ? 'rejectBackoffice'
            : 'reviewBackoffice';

        foreach ($documents as $document) {
            Gate::forUser($actor)->authorize(
                $documentAbility,
                $document,
            );
        }
    }

    /**
     * @param  Collection<int, AdministrativeProcess>  $processes
     * @param  Collection<int, DocumentSubmission>  $documents
     * @return list<string>
     */
    private function blockers(
        BulkApplicationReviewAction $action,
        Collection $processes,
        Collection $documents,
    ): array {
        $blockers = [];

        if ($action === BulkApplicationReviewAction::MarkReadyForClosure) {
            foreach ($processes as $process) {
                $readiness = $this->readinessService->forProcess(
                    $process,
                );

                if (! $readiness['ready']) {
                    $blockers[] = sprintf(
                        '%s: %s',
                        $process->process_number,
                        implode('; ', $readiness['blockers']),
                    );
                }
            }
        }

        if ($action === BulkApplicationReviewAction::ReopenReview) {
            foreach ($processes as $process) {
                if (! $process->latestDocumentalReview?->isReadyForClosure()) {
                    $blockers[] = sprintf(
                        '%s: a análise não está pronta para fecho.',
                        $process->process_number,
                    );
                }
            }
        }

        if ($action->isDocumentAction()) {
            foreach ($documents as $document) {
                if (! $this->documentTransitionAllowed(
                    $action,
                    $document->status,
                )) {
                    $blockers[] = sprintf(
                        'Documento %d: o estado %s não permite a operação %s.',
                        $document->id,
                        $document->status->label(),
                        $action->label(),
                    );
                }
            }
        }

        return $blockers;
    }

    private function documentTransitionAllowed(
        BulkApplicationReviewAction $action,
        DocumentStatus $status,
    ): bool {
        return match ($action) {
            BulkApplicationReviewAction::MarkDocumentsUnderReview => $status
                === DocumentStatus::Submitted,
            BulkApplicationReviewAction::ValidateDocuments,
            BulkApplicationReviewAction::RejectDocuments => in_array(
                $status,
                [
                    DocumentStatus::Submitted,
                    DocumentStatus::UnderReview,
                ],
                true,
            ),
            default => true,
        };
    }

    /**
     * @param  Collection<int, AdministrativeProcess>  $processes
     */
    private function assignAnalyst(
        Collection $processes,
        ?int $assigneeId,
        User $actor,
    ): void {
        $assignee = $this->municipalScope
            ->users(User::query(), $actor)
            ->whereKey($assigneeId)
            ->where('status', 'active')
            ->firstOrFail();

        foreach ($processes as $process) {
            $this->processService->assign(
                $process,
                $assignee,
                $actor,
            );
        }
    }

    /**
     * @param  Collection<int, DocumentSubmission>  $documents
     */
    private function transitionDocuments(
        Collection $documents,
        User $actor,
        BulkApplicationReviewAction $action,
        ?string $reason,
        ?string $internalNotes,
    ): void {
        /** @var array<int, AdministrativeProcess> $processes */
        $processes = [];

        foreach ($documents as $document) {
            match ($action) {
                BulkApplicationReviewAction::MarkDocumentsUnderReview => $this->documentReviewService
                    ->markUnderReview(
                        $document,
                        $actor,
                        $internalNotes,
                    ),
                BulkApplicationReviewAction::ValidateDocuments => $this->documentReviewService
                    ->validate(
                        $document,
                        $actor,
                        $internalNotes,
                    ),
                BulkApplicationReviewAction::RejectDocuments => $this->documentReviewService
                    ->reject(
                        $document,
                        $actor,
                        (string) $reason,
                        $internalNotes,
                    ),
                default => throw new AuthorizationException(
                    'A operação documental não é suportada.',
                ),
            };

            $process = $document
                ->application
                ?->administrativeProcess;

            if ($process instanceof AdministrativeProcess) {
                $processes[$process->id] = $process;
            }
        }

        foreach ($processes as $process) {
            $this->progressiveReviewService->touchActivity(
                $process,
                $actor,
            );
        }
    }

    /**
     * @param  Collection<int, AdministrativeProcess>  $processes
     */
    private function markReady(
        Collection $processes,
        User $actor,
    ): void {
        foreach ($processes as $process) {
            $this->progressiveReviewService
                ->markReadyForClosure($process, $actor);
        }
    }

    /**
     * @param  Collection<int, AdministrativeProcess>  $processes
     */
    private function reopen(
        Collection $processes,
        User $actor,
        ?string $reason,
    ): void {
        foreach ($processes as $process) {
            $this->progressiveReviewService->reopen(
                $process,
                $actor,
                $reason,
            );
        }
    }

    /**
     * @param  array{
     *     action: BulkApplicationReviewAction,
     *     process_ids: list<int>,
     *     document_ids: list<int>,
     *     assigned_to: int|null,
     *     reason: string|null,
     *     internal_notes: string|null,
     *     preview_token: string|null
     * }  $payload
     * @param  Collection<int, AdministrativeProcess>  $processes
     * @param  Collection<int, DocumentSubmission>  $documents
     * @param  Collection<int, DocumentSubmission>  $readinessDocuments
     *
     * @throws JsonException
     */
    private function token(
        Contest $contest,
        User $actor,
        array $payload,
        Collection $processes,
        Collection $documents,
        Collection $readinessDocuments,
        ?User $assignee,
    ): string {
        $processFingerprint = [];

        foreach ($processes as $process) {
            $application = $process->application;
            $registration = $application instanceof Application
                ? $application->adhesionRegistration
                : null;
            $review = $process->latestDocumentalReview;

            $processFingerprint[] = [
                'id' => $process->id,
                'state' => $this->modelStateFingerprint($process),
                'application_state' => $application instanceof Application
                    ? $this->modelStateFingerprint($application)
                    : null,
                'registration_state' => $registration instanceof Model
                    ? $this->modelStateFingerprint($registration)
                    : null,
                'review_state' => $review !== null
                    ? $this->modelStateFingerprint($review)
                    : null,
            ];
        }

        $documentFingerprint = [];

        $fingerprintDocuments = $documents
            ->concat($readinessDocuments)
            ->unique('id')
            ->sortBy('id')
            ->values();

        foreach ($fingerprintDocuments as $document) {
            $documentFingerprint[] = [
                'id' => $document->id,
                'state' => $this->modelStateFingerprint($document),
            ];
        }

        $fingerprint = [
            'contest_id' => $contest->id,
            'actor_id' => $actor->id,
            'action' => $payload['action']->value,
            'assigned_to' => $payload['assigned_to'],
            'assignee_state' => $assignee !== null
                ? $this->modelStateFingerprint($assignee)
                : null,
            'reason' => $payload['reason'],
            'internal_notes' => $payload['internal_notes'],
            'processes' => $processFingerprint,
            'documents' => $documentFingerprint,
        ];

        return hash_hmac(
            'sha256',
            json_encode(
                $fingerprint,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
            ),
            (string) config('app.key'),
        );
    }

    /**
     * Build a deterministic fingerprint from raw persisted attributes.
     *
     * Database timestamps can have second-level precision, especially in
     * SQLite and some MySQL configurations. Hashing the complete persisted
     * state prevents a same-second update from reusing a stale preview token.
     *
     * @throws JsonException
     */
    private function modelStateFingerprint(Model $model): string
    {
        $attributes = $model->getRawOriginal();
        ksort($attributes);

        return hash(
            'sha256',
            json_encode(
                $attributes,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
            ),
        );
    }
}
