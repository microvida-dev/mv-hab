<?php

namespace App\Services\Administrative;

use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\ApplicationReviewPublicationStatus;
use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDeliveryStatus;
use App\Enums\NotificationPriority;
use App\Enums\OfficialNotificationChannel;
use App\Enums\OfficialNotificationStatus;
use App\Enums\OfficialNotificationType;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewBatchItem;
use App\Models\ApplicationReviewPublication;
use App\Models\ApplicationReviewPublicationResult;
use App\Models\OfficialNotification;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Notifications\CommunicationDeliveryService;
use App\Services\Notifications\CommunicationLogService;
use App\Services\Notifications\CommunicationNumberService;
use App\Services\Notifications\ProceduralEmailDeliveryService;
use App\Services\Platform\PlatformOperatorScopeService;
use App\Services\Support\CanonicalJsonHasher;
use App\Support\AuditEvents;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;

class ApplicationReviewPublicationService
{
    private const EVENT_CODE = 'application_review_result_published';

    public function __construct(
        private readonly CanonicalJsonHasher $hasher,
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly PlatformOperatorScopeService $platformScope,
        private readonly CommunicationLogService $communications,
        private readonly CommunicationNumberService $numbers,
        private readonly CommunicationDeliveryService $deliveries,
        private readonly ProceduralEmailDeliveryService $proceduralEmails,
        private readonly AuditLogger $audit,
    ) {}

    /** @return LengthAwarePaginator<int, ApplicationReviewPublication> */
    public function paginate(User $actor): LengthAwarePaginator
    {
        $query = ApplicationReviewPublication::query()
            ->with(['contest.program', 'batch', 'publishedBy'])
            ->withCount('results')
            ->latest('published_at');

        if (! $this->platformScope->hasGlobalScope($actor)) {
            if ($actor->municipality_id === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('municipality_id', $actor->municipality_id);
            }
        }

        return $query->paginate(25);
    }

    public function existingForBatch(
        ApplicationReviewBatch $batch,
    ): ?ApplicationReviewPublication {
        return ApplicationReviewPublication::query()
            ->where('application_review_batch_id', $batch->id)
            ->first();
    }

    /**
     * @return array{
     *     batch: ApplicationReviewBatch,
     *     reason: string,
     *     item_count: int,
     *     publication_hash: string,
     *     source_snapshot_hash: string,
     *     token: string,
     *     items: list<array{
     *         batch_item_id: int,
     *         process_number: string,
     *         application_number: string|null,
     *         outcome: ApplicationReviewBatchOutcome,
     *         outcome_label: string,
     *         technical_result: string|null,
     *         message: string,
     *         next_action: string,
     *         result_hash: string,
     *         notification_hash: string,
     *         result_payload: array<string, mixed>
     *     }>
     * }
     *
     * @throws JsonException
     */
    public function preview(
        ApplicationReviewBatch $batch,
        User $actor,
        string $reason,
    ): array {
        $batch->loadMissing(['contest.program', 'items']);
        $this->assertBatchScope($batch, $actor);

        if ($this->existingForBatch($batch) instanceof ApplicationReviewPublication) {
            throw ValidationException::withMessages([
                'publication' => 'Este lote já possui uma publicação coletiva.',
            ]);
        }

        return $this->buildPreview($batch, $actor, $reason);
    }

    /**
     * @param  array{reason: string, preview_token: string|null}  $payload
     *
     * @throws JsonException
     */
    public function publish(
        ApplicationReviewBatch $batch,
        User $actor,
        array $payload,
    ): ApplicationReviewPublication {
        $token = trim((string) ($payload['preview_token'] ?? ''));

        if ($token === '') {
            throw ValidationException::withMessages([
                'preview_token' => 'A publicação deve ser previamente confirmada.',
            ]);
        }

        return DB::transaction(function () use (
            $batch,
            $actor,
            $payload,
            $token,
        ): ApplicationReviewPublication {
            $lockedBatch = ApplicationReviewBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedBatch->contest()->lockForUpdate()->firstOrFail();

            $existing = ApplicationReviewPublication::query()
                ->where('application_review_batch_id', $lockedBatch->id)
                ->lockForUpdate()
                ->first();

            if ($existing instanceof ApplicationReviewPublication) {
                return $existing->load([
                    'batch',
                    'contest.program',
                    'publishedBy',
                    'results',
                ]);
            }

            $items = ApplicationReviewBatchItem::query()
                ->where('application_review_batch_id', $lockedBatch->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $lockedBatch->setRelation('items', $items);
            $lockedBatch->loadMissing('contest.program');
            $this->assertBatchScope($lockedBatch, $actor);
            $preview = $this->buildPreview(
                $lockedBatch,
                $actor,
                trim((string) $payload['reason']),
            );

            if (! hash_equals($preview['token'], $token)) {
                throw ValidationException::withMessages([
                    'preview_token' => 'O lote ou a confirmação da publicação foi alterado. Gere uma nova pré-visualização.',
                ]);
            }

            $publishedAt = now('UTC');
            $publication = new ApplicationReviewPublication([
                'municipality_id' => $lockedBatch->municipality_id,
                'contest_id' => $lockedBatch->contest_id,
                'application_review_batch_id' => $lockedBatch->id,
                'cycle' => $lockedBatch->cycle,
                'sequence_number' => $lockedBatch->sequence_number,
                'status' => ApplicationReviewPublicationStatus::Published,
                'reason' => $preview['reason'],
                'item_count' => $preview['item_count'],
                'publication_key' => hash('sha256', $token),
                'source_snapshot_hash' => $preview['source_snapshot_hash'],
                'publication_hash' => $preview['publication_hash'],
            ]);
            $publication->forceFill([
                'public_id' => (string) Str::orderedUuid(),
                'published_by' => $actor->id,
                'published_at' => $publishedAt,
            ])->save();

            foreach ($preview['items'] as $prepared) {
                $batchItem = $items->firstWhere(
                    'id',
                    $prepared['batch_item_id'],
                );

                if (! $batchItem instanceof ApplicationReviewBatchItem) {
                    throw ValidationException::withMessages([
                        'publication' => 'Um item do lote deixou de estar disponível.',
                    ]);
                }

                $process = AdministrativeProcess::query()
                    ->whereKey($batchItem->administrative_process_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $application = Application::query()
                    ->whereKey($batchItem->application_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $recipient = User::query()
                    ->whereKey($application->user_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertRecipientConsistency(
                    $lockedBatch,
                    $batchItem,
                    $process,
                    $application,
                    $recipient,
                );

                $resultPublicId = (string) Str::orderedUuid();
                $actionUrl = route(
                    'candidate.application-review-results.show',
                    ['reviewResult' => $resultPublicId],
                );
                $content = $this->notificationContent(
                    $prepared['result_payload'],
                );
                $communication = $this->communications->create(
                    eventCode: self::EVENT_CODE,
                    recipient: $recipient,
                    content: $content,
                    related: $publication,
                    actor: $actor,
                    priority: NotificationPriority::High,
                    official: true,
                    requiresAcknowledgement: false,
                );
                $notification = new OfficialNotification([
                    'notification_type' => OfficialNotificationType::ApplicationReviewResultPublished,
                    'channel' => OfficialNotificationChannel::CandidateArea,
                    'subject' => $content['subject'],
                    'title' => $content['title'],
                    'body' => $content['body'],
                    'action_url' => $actionUrl,
                ]);
                $notification->forceFill([
                    'notification_number' => $this->numbers->notification(),
                    'user_id' => $recipient->id,
                    'recipient_email' => $recipient->email,
                    'application_id' => $application->id,
                    'communication_log_id' => $communication->id,
                    'notifiable_type' => $publication->getMorphClass(),
                    'notifiable_id' => $publication->id,
                    'event_code' => self::EVENT_CODE,
                    'status' => OfficialNotificationStatus::Published,
                    'priority' => NotificationPriority::High,
                    'requires_acknowledgement' => false,
                    'created_by' => $actor->id,
                    'sent_at' => $publishedAt,
                    'delivered_at' => $publishedAt,
                ])->save();

                $inAppDelivery = $this->deliveries->create(
                    $communication,
                    CommunicationChannel::InApp,
                    null,
                    $notification,
                );
                $inAppDelivery->forceFill([
                    'status' => CommunicationDeliveryStatus::Delivered,
                    'provider' => 'mvhab_atomic_publication',
                    'queued_at' => $publishedAt,
                    'processing_at' => $publishedAt,
                    'sent_at' => $publishedAt,
                    'delivered_at' => $publishedAt,
                ])->save();

                $emailDelivery = $this->proceduralEmails->ensureQueued(
                    $communication,
                    $recipient,
                    $notification,
                );

                ApplicationReviewPublicationResult::query()->create([
                    'public_id' => $resultPublicId,
                    'application_review_publication_id' => $publication->id,
                    'application_review_batch_item_id' => $batchItem->id,
                    'municipality_id' => $publication->municipality_id,
                    'contest_id' => $publication->contest_id,
                    'administrative_process_id' => $process->id,
                    'application_id' => $application->id,
                    'user_id' => $recipient->id,
                    'process_number' => $batchItem->process_number,
                    'application_number' => $batchItem->application_number,
                    'application_public_id' => $batchItem->application_public_id,
                    'outcome' => $batchItem->outcome,
                    'technical_result' => $batchItem->technical_result,
                    'result_payload' => $prepared['result_payload'],
                    'source_snapshot_hash' => $batchItem->snapshot_hash,
                    'result_hash' => $prepared['result_hash'],
                    'notification_hash' => $prepared['notification_hash'],
                    'official_notification_id' => $notification->id,
                    'communication_log_id' => $communication->id,
                    'in_app_delivery_id' => $inAppDelivery->id,
                    'email_delivery_id' => $emailDelivery->id,
                    'published_at' => $publishedAt,
                ]);
            }

            $this->audit->record(
                event: AuditEvents::CREATE,
                auditable: $publication,
                module: 'administrative_processes',
                action: 'application_review_publication_published',
                description: 'Resultados da revisão documental publicados coletivamente.',
                newValues: [
                    'status' => ApplicationReviewPublicationStatus::Published->value,
                    'cycle' => $publication->cycle->value,
                    'item_count' => $publication->item_count,
                    'publication_hash' => $publication->publication_hash,
                ],
                metadata: [
                    'actor_id' => $actor->id,
                    'contest_id' => $publication->contest_id,
                    'batch_id' => $publication->application_review_batch_id,
                    'published_at' => $publishedAt->toIso8601String(),
                ],
            );

            return $publication->load([
                'batch',
                'contest.program',
                'publishedBy',
                'results',
            ]);
        }, 3);
    }

    /**
     * @return array{
     *     batch: ApplicationReviewBatch,
     *     reason: string,
     *     item_count: int,
     *     publication_hash: string,
     *     source_snapshot_hash: string,
     *     token: string,
     *     items: list<array{
     *         batch_item_id: int,
     *         process_number: string,
     *         application_number: string|null,
     *         outcome: ApplicationReviewBatchOutcome,
     *         outcome_label: string,
     *         technical_result: string|null,
     *         message: string,
     *         next_action: string,
     *         result_hash: string,
     *         notification_hash: string,
     *         result_payload: array<string, mixed>
     *     }>
     * }
     *
     * @throws JsonException
     */
    private function buildPreview(
        ApplicationReviewBatch $batch,
        User $actor,
        string $reason,
    ): array {
        if ($batch->status !== ApplicationReviewBatchStatus::Sealed) {
            throw ValidationException::withMessages([
                'publication' => 'Apenas um lote selado e não substituído pode ser publicado.',
            ]);
        }

        $items = $batch->relationLoaded('items')
            ? $batch->items
            : $batch->items()->orderBy('id')->get();
        $items = $items->sortBy('id')->values();

        if ($items->count() !== (int) $batch->item_count || $items->isEmpty()) {
            throw ValidationException::withMessages([
                'publication' => 'O lote não contém o conjunto integral de resultados esperado.',
            ]);
        }

        $batchItems = [];
        $prepared = [];

        foreach ($items as $item) {
            if (! hash_equals(
                $item->snapshot_hash,
                $this->hasher->hash($item->snapshot_payload),
            )) {
                throw ValidationException::withMessages([
                    'publication' => 'A integridade de um snapshot do lote não pôde ser confirmada.',
                ]);
            }

            $this->assertItemProjectionMatchesSnapshot($item);

            $batchItems[] = [
                'application_id' => (int) $item->application_id,
                'snapshot_hash' => $item->snapshot_hash,
                'payload' => $item->snapshot_payload,
            ];
            $resultPayload = $this->candidatePayload($batch, $item);
            $resultHash = $this->hasher->hash($resultPayload);
            $notificationHash = $this->hasher->hash([
                'event_code' => self::EVENT_CODE,
                'result_hash' => $resultHash,
            ]);
            $prepared[] = [
                'batch_item_id' => (int) $item->id,
                'process_number' => $item->process_number,
                'application_number' => $item->application_number,
                'outcome' => $item->outcome,
                'outcome_label' => $item->outcome->label(),
                'technical_result' => $item->technical_result,
                'message' => (string) $resultPayload['message'],
                'next_action' => (string) $resultPayload['next_action'],
                'result_hash' => $resultHash,
                'notification_hash' => $notificationHash,
                'result_payload' => $resultPayload,
            ];
        }

        $recomputedBatchHash = $this->hasher->hash([
            'schema_version' => 1,
            'contest_id' => $batch->contest_id,
            'cycle' => $batch->cycle->value,
            'items' => $batchItems,
        ]);

        if (! hash_equals($batch->snapshot_hash, $recomputedBatchHash)) {
            throw ValidationException::withMessages([
                'publication' => 'O hash coletivo do lote não corresponde aos snapshots persistidos.',
            ]);
        }

        $publicationHash = $this->hasher->hash([
            'schema_version' => 1,
            'batch_id' => $batch->id,
            'batch_public_id' => $batch->public_id,
            'contest_id' => $batch->contest_id,
            'cycle' => $batch->cycle->value,
            'sequence_number' => $batch->sequence_number,
            'reason' => $reason,
            'source_snapshot_hash' => $batch->snapshot_hash,
            'results' => array_map(
                static fn (array $item): array => [
                    'batch_item_id' => $item['batch_item_id'],
                    'result_hash' => $item['result_hash'],
                    'notification_hash' => $item['notification_hash'],
                ],
                $prepared,
            ),
        ]);
        $token = hash_hmac(
            'sha256',
            $this->hasher->encode([
                'actor_id' => $actor->id,
                'batch_id' => $batch->id,
                'reason' => $reason,
                'source_snapshot_hash' => $batch->snapshot_hash,
                'publication_hash' => $publicationHash,
            ]),
            (string) config('app.key'),
        );

        return [
            'batch' => $batch,
            'reason' => $reason,
            'item_count' => count($prepared),
            'publication_hash' => $publicationHash,
            'source_snapshot_hash' => $batch->snapshot_hash,
            'token' => $token,
            'items' => $prepared,
        ];
    }

    private function assertItemProjectionMatchesSnapshot(
        ApplicationReviewBatchItem $item,
    ): void {
        $payload = $item->snapshot_payload;
        $process = is_array($payload['process'] ?? null)
            ? $payload['process']
            : [];
        $application = is_array($payload['application'] ?? null)
            ? $payload['application']
            : [];
        $valid = (int) ($process['id'] ?? 0)
                === (int) $item->administrative_process_id
            && (string) ($process['number'] ?? '')
                === (string) $item->process_number
            && (int) ($application['id'] ?? 0)
                === (int) $item->application_id
            && (string) ($application['public_id'] ?? '')
                === (string) $item->application_public_id
            && ($application['number'] ?? null)
                === $item->application_number
            && (string) ($payload['outcome'] ?? '')
                === $item->outcome->value
            && ($payload['technical_result'] ?? null)
                === $item->technical_result;

        if (! $valid) {
            throw ValidationException::withMessages([
                'publication' => 'A projeção persistida de um item não corresponde ao snapshot imutável.',
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function candidatePayload(
        ApplicationReviewBatch $batch,
        ApplicationReviewBatchItem $item,
    ): array {
        [$message, $nextAction] = match ($item->outcome) {
            ApplicationReviewBatchOutcome::CompletePendingDecision => [
                'A revisão documental terminou sem bloqueios documentais. Este resultado não constitui admissão, elegibilidade, classificação, atribuição ou decisão final.',
                'await_formal_decision',
            ],
            ApplicationReviewBatchOutcome::CorrectionRequired => [
                'A revisão documental identificou elementos que necessitam de aperfeiçoamento. O pedido formal, os elementos concretos e o prazo serão disponibilizados separadamente.',
                'await_correction_request',
            ],
            ApplicationReviewBatchOutcome::Withdrawn => [
                'A desistência da candidatura encontra-se registada neste ciclo de revisão.',
                'none',
            ],
            ApplicationReviewBatchOutcome::NotAssessed => [
                'A candidatura não foi avaliada neste ciclo de revisão documental.',
                'await_municipal_information',
            ],
        };

        return [
            'schema_version' => 1,
            'cycle' => $batch->cycle->value,
            'cycle_label' => $batch->cycle->label(),
            'process_number' => $item->process_number,
            'application_number' => $item->application_number,
            'application_public_id' => $item->application_public_id,
            'outcome' => $item->outcome->value,
            'outcome_label' => $item->outcome->label(),
            'technical_result' => $item->technical_result,
            'message' => $message,
            'next_action' => $nextAction,
            'source_snapshot_hash' => $item->snapshot_hash,
        ];
    }

    /**
     * @param  array<string, mixed>  $resultPayload
     * @return array{subject: string, title: string, body: string}
     */
    private function notificationContent(array $resultPayload): array
    {
        return [
            'subject' => 'Resultado da revisão documental disponível',
            'title' => 'Resultado da revisão documental disponível',
            'body' => sprintf(
                "%s\n\nProcesso: %s\nResultado: %s\n\nConsulte o detalhe na sua área pessoal.",
                (string) $resultPayload['message'],
                (string) $resultPayload['process_number'],
                (string) $resultPayload['outcome_label'],
            ),
        ];
    }

    private function assertBatchScope(
        ApplicationReviewBatch $batch,
        User $actor,
    ): void {
        if (! $this->municipalScope->ownsContest($actor, $batch->contest)) {
            abort(403);
        }

        $program = $batch->contest->program;

        if (! $program instanceof Program) {
            throw ValidationException::withMessages([
                'publication' => 'O concurso não possui um programa municipal válido.',
            ]);
        }

        if ((int) $batch->municipality_id !== (int) $program->municipality_id) {
            throw ValidationException::withMessages([
                'publication' => 'O lote não possui um contexto municipal coerente.',
            ]);
        }
    }

    private function assertRecipientConsistency(
        ApplicationReviewBatch $batch,
        ApplicationReviewBatchItem $item,
        AdministrativeProcess $process,
        Application $application,
        User $recipient,
    ): void {
        $valid = (int) $item->administrative_process_id === (int) $process->id
            && (int) $item->application_id === (int) $application->id
            && (int) $process->application_id === (int) $application->id
            && (int) $process->contest_id === (int) $batch->contest_id
            && (int) $application->contest_id === (int) $batch->contest_id
            && (int) $process->user_id === (int) $application->user_id
            && (int) $recipient->id === (int) $application->user_id
            && (int) $recipient->municipality_id === (int) $batch->municipality_id
            && (string) $application->public_id === $item->application_public_id;

        if (! $valid) {
            throw ValidationException::withMessages([
                'publication' => 'O destinatário ou a candidatura de um resultado não é coerente com o lote selado.',
            ]);
        }
    }
}
