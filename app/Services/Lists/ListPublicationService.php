<?php

namespace App\Services\Lists;

use App\Enums\DefinitiveListStatus;
use App\Enums\ListPublicationChannel;
use App\Enums\ListPublicationStatus;
use App\Enums\ListPublicationType;
use App\Enums\OfficialNotificationType;
use App\Enums\ProvisionalListStatus;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use App\Models\ListPublication;
use App\Models\ProvisionalList;
use App\Models\ProvisionalListEntry;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\OfficialNotificationService;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListPublicationService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly OfficialNotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function publishProvisional(ProvisionalList $list, User $actor, array $data = []): ListPublication
    {
        if ($this->provisionalStatus($list) !== ProvisionalListStatus::Approved) {
            throw ValidationException::withMessages(['provisional_list' => 'A lista provisória deve estar aprovada antes da publicação.']);
        }

        $notificationService = $this->notificationService;

        return $this->publish($list, $actor, ListPublicationType::ProvisionalList, $data, function () use ($list, $actor, $notificationService) {
            $list->forceFill([
                'status' => ProvisionalListStatus::Published,
                'published_by' => $actor->id,
                'published_at' => now(),
                'public_visibility' => (bool) $list->public_visibility,
            ])->save();

            $list->entries()->with('application')->each(function ($entry) use ($list, $actor, $notificationService) {
                $notificationService->createInternal(
                    user: $this->requiredCandidate($entry),
                    type: OfficialNotificationType::ProvisionalListPublished,
                    subject: 'Lista provisória publicada',
                    body: 'A lista provisória do concurso foi publicada e pode ser consultada na área reservada.',
                    notifiable: $list,
                    application: $entry->application,
                    actor: $actor,
                );
            });
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function publishDefinitive(DefinitiveList $list, User $actor, array $data = []): ListPublication
    {
        if ($this->definitiveStatus($list) !== DefinitiveListStatus::Approved) {
            throw ValidationException::withMessages(['definitive_list' => 'A lista definitiva deve estar aprovada antes da publicação.']);
        }

        $notificationService = $this->notificationService;

        return $this->publish($list, $actor, ListPublicationType::DefinitiveList, $data, function () use ($list, $actor, $notificationService) {
            $list->forceFill([
                'status' => DefinitiveListStatus::Published,
                'published_by' => $actor->id,
                'published_at' => now(),
                'public_visibility' => (bool) $list->public_visibility,
            ])->save();

            $list->entries()->with('application')->each(function ($entry) use ($list, $actor, $notificationService) {
                $notificationService->createInternal(
                    user: $this->requiredCandidate($entry),
                    type: OfficialNotificationType::DefinitiveListPublished,
                    subject: 'Lista definitiva publicada',
                    body: 'A lista definitiva encontra-se disponível. Esta lista resulta da análise das candidaturas, reclamações e demais atos procedimentais aplicáveis.',
                    notifiable: $list,
                    application: $entry->application,
                    actor: $actor,
                );
            });
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function publish(ProvisionalList|DefinitiveList $list, User $actor, ListPublicationType $type, array $data, callable $statusUpdater): ListPublication
    {
        return DB::transaction(function () use ($list, $actor, $type, $data, $statusUpdater) {
            $statusUpdater();

            $publication = new ListPublication([
                'publication_type' => $type,
                'status' => ListPublicationStatus::Published,
                'channel' => $data['channel'] ?? ListPublicationChannel::CandidateArea,
                'title' => $data['title'] ?? $list->title,
                'summary' => $data['summary'] ?? $list->description,
                'public_url' => $data['public_url'] ?? null,
                'internal_url' => $data['internal_url'] ?? null,
                'visibility_starts_at' => $data['visibility_starts_at'] ?? $list->publication_starts_at ?? now(),
                'visibility_ends_at' => $data['visibility_ends_at'] ?? $list->publication_ends_at,
                'anonymization_mode' => $data['anonymization_mode'] ?? $list->anonymization_mode,
            ]);
            $publication->forceFill([
                'published_by' => $actor->id,
                'published_at' => now(),
            ]);
            $list->publications()->save($publication);

            $this->auditLogger->record(
                AuditEvents::PUBLISH,
                $list,
                'public_lists',
                $type === ListPublicationType::ProvisionalList ? 'provisional_list_publish' : 'definitive_list_publish',
                $type->label().' publicada de forma controlada.',
                metadata: ['publication_id' => $publication->id, 'channel' => $this->publicationChannelValue($publication)],
            );

            return $publication;
        });
    }

    private function provisionalStatus(ProvisionalList $list): ?ProvisionalListStatus
    {
        $status = $list->getAttribute('status');

        if ($status instanceof ProvisionalListStatus) {
            return $status;
        }

        return is_string($status) ? ProvisionalListStatus::tryFrom($status) : null;
    }

    private function definitiveStatus(DefinitiveList $list): ?DefinitiveListStatus
    {
        $status = $list->getAttribute('status');

        if ($status instanceof DefinitiveListStatus) {
            return $status;
        }

        return is_string($status) ? DefinitiveListStatus::tryFrom($status) : null;
    }

    private function publicationChannelValue(ListPublication $publication): string
    {
        $channel = $publication->getAttribute('channel');

        if ($channel instanceof ListPublicationChannel) {
            return $channel->value;
        }

        return is_string($channel) ? $channel : ListPublicationChannel::CandidateArea->value;
    }

    private function requiredCandidate(ProvisionalListEntry|DefinitiveListEntry $entry): User
    {
        $candidate = $entry->candidate;

        if (! $candidate instanceof User) {
            throw ValidationException::withMessages(['candidate' => 'A entrada da lista não tem candidato associado.']);
        }

        return $candidate;
    }
}
