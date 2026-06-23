<?php

namespace App\Services\Lists;

use App\Enums\ComplaintDecisionResult;
use App\Enums\ComplaintStatus;
use App\Enums\DefinitiveListStatus;
use App\Enums\HearingStatus;
use App\Enums\ListChangeType;
use App\Enums\ListEntryStatus;
use App\Enums\ListPublicationChannel;
use App\Enums\ProvisionalListStatus;
use App\Models\Application;
use App\Models\ComplaintDecision;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use App\Models\ProvisionalList;
use App\Models\ProvisionalListEntry;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DefinitiveListService
{
    public function __construct(
        private readonly ListPublicationService $publicationService,
        private readonly ListChangeLogService $changeLogService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function generateFromProvisional(ProvisionalList $provisionalList, array $data, User $actor): DefinitiveList
    {
        $provisionalList->loadMissing(['entries.application', 'entries.applicationScore', 'entries.rankingEntry', 'complaints.decision', 'hearings']);

        if ($this->provisionalStatus($provisionalList) !== ProvisionalListStatus::ComplaintPeriodClosed) {
            throw ValidationException::withMessages(['provisional_list_id' => 'A lista definitiva só pode ser gerada após fecho do prazo de reclamação.']);
        }

        if ($this->hasPendingComplaints($provisionalList)) {
            throw ValidationException::withMessages(['provisional_list_id' => 'Existem reclamações pendentes.']);
        }

        if ($this->hasPendingHearings($provisionalList)) {
            throw ValidationException::withMessages(['provisional_list_id' => 'Existem audiências pendentes.']);
        }

        $changeLogService = $this->changeLogService;

        return DB::transaction(function () use ($provisionalList, $data, $actor, $changeLogService) {
            $list = new DefinitiveList([
                'program_id' => $provisionalList->program_id,
                'contest_id' => $provisionalList->contest_id,
                'provisional_list_id' => $provisionalList->id,
                'ranking_snapshot_id' => $provisionalList->ranking_snapshot_id,
                'scoring_run_id' => $provisionalList->scoring_run_id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'publication_starts_at' => $data['publication_starts_at'] ?? null,
                'publication_ends_at' => $data['publication_ends_at'] ?? null,
                'anonymization_mode' => $data['anonymization_mode'] ?? $provisionalList->anonymization_mode,
                'public_visibility' => (bool) ($data['public_visibility'] ?? $provisionalList->public_visibility),
                'legal_basis' => $data['legal_basis'] ?? $provisionalList->legal_basis,
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);
            $list->forceFill([
                'list_number' => $this->generateListNumber(),
                'status' => DefinitiveListStatus::Draft,
                'version_number' => 1,
                'generated_by' => $actor->id,
                'generated_at' => now(),
            ])->save();

            foreach ($provisionalList->entries as $entry) {
                $decision = $provisionalList->complaints
                    ->first(fn ($complaint) => $complaint->application_id === $entry->application_id)
                    ?->decision;
                $acceptedEffect = $decision instanceof ComplaintDecision
                    && $this->complaintDecisionResultIsIn($decision, [ComplaintDecisionResult::Accepted, ComplaintDecisionResult::PartiallyAccepted])
                    && $this->complaintDecisionRequiresListUpdate($decision);
                $decisionSummary = $acceptedEffect ? $this->complaintDecisionSummary($decision) : null;

                $definitiveEntry = new DefinitiveListEntry;
                $definitiveEntry->forceFill([
                    'definitive_list_id' => $list->id,
                    'provisional_list_entry_id' => $entry->id,
                    'application_id' => $entry->application_id,
                    'application_score_id' => $entry->application_score_id,
                    'ranking_entry_id' => $entry->ranking_entry_id,
                    'user_id' => $entry->user_id,
                    'entry_type' => $entry->entry_type,
                    'status' => $acceptedEffect ? ListEntryStatus::ChangedAfterComplaint : $entry->status,
                    'rank_position' => $entry->rank_position,
                    'previous_rank_position' => $entry->rank_position,
                    'total_score' => $entry->total_score,
                    'previous_total_score' => $entry->total_score,
                    'public_identifier' => $entry->public_identifier,
                    'candidate_name_masked' => $entry->candidate_name_masked,
                    'application_number_masked' => $entry->application_number_masked,
                    'exclusion_reason' => $entry->exclusion_reason,
                    'exclusion_legal_basis' => $entry->exclusion_legal_basis,
                    'decision_summary' => $decisionSummary ?? $entry->decision_summary,
                    'change_reason' => $acceptedEffect ? 'Efeito de reclamação aprovada: '.$decisionSummary : null,
                    'changed_after_complaint' => $acceptedEffect,
                    'metadata' => ['source' => 'provisional_list'],
                ])->save();

                if ($acceptedEffect) {
                    $application = $entry->application;

                    if (! $application instanceof Application) {
                        throw ValidationException::withMessages(['application' => 'A entrada da lista não tem candidatura associada.']);
                    }

                    $changeLogService->record(
                        type: ListChangeType::ComplaintEffect,
                        application: $application,
                        provisionalList: $provisionalList,
                        definitiveList: $list,
                        actor: $actor,
                        source: $decision,
                        from: $this->entryStatusValue($entry),
                        to: ListEntryStatus::ChangedAfterComplaint->value,
                        reason: $decisionSummary,
                    );
                }
            }

            $this->auditLogger->record(
                AuditEvents::CREATE,
                $list,
                'public_lists',
                'definitive_list_generate',
                'Lista definitiva gerada após reclamações e audiências.',
                metadata: ['provisional_list_id' => $provisionalList->id],
            );

            $list->refresh();

            return $list->load(['entries', 'provisionalList']);
        });
    }

    public function sendToReview(DefinitiveList $list, User $actor): DefinitiveList
    {
        if ($this->definitiveStatus($list) !== DefinitiveListStatus::Draft) {
            throw ValidationException::withMessages(['definitive_list' => 'Apenas listas definitivas em rascunho podem seguir para revisão.']);
        }

        $list->forceFill(['status' => DefinitiveListStatus::UnderReview, 'reviewed_by' => $actor->id, 'reviewed_at' => now()])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $list, 'public_lists', 'definitive_list_review', 'Lista definitiva enviada para revisão.');

        return $list->refresh();
    }

    public function approve(DefinitiveList $list, User $actor): DefinitiveList
    {
        if (! $this->definitiveStatusIsIn($list, [DefinitiveListStatus::Draft, DefinitiveListStatus::UnderReview])) {
            throw ValidationException::withMessages(['definitive_list' => 'A lista definitiva não está num estado aprovável.']);
        }

        $list->forceFill(['status' => DefinitiveListStatus::Approved, 'approved_by' => $actor->id, 'approved_at' => now()])->save();
        $this->auditLogger->record(AuditEvents::APPROVE, $list, 'public_lists', 'definitive_list_approve', 'Lista definitiva aprovada.');

        return $list->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function publish(DefinitiveList $list, User $actor, array $data = []): DefinitiveList
    {
        $data['channel'] ??= $list->public_visibility ? ListPublicationChannel::PublicPortal : ListPublicationChannel::CandidateArea;
        $publication = $this->publicationService->publishDefinitive($list, $actor, $data);

        if ($list->public_visibility) {
            $publication->forceFill(['public_url' => route('public.results.show', $publication, false)])->save();
        }

        return $list->refresh();
    }

    public function lock(DefinitiveList $list, User $actor): DefinitiveList
    {
        if ($this->definitiveStatus($list) !== DefinitiveListStatus::Published) {
            throw ValidationException::withMessages(['definitive_list' => 'A lista definitiva deve estar publicada antes de ser bloqueada.']);
        }

        $list->forceFill(['status' => DefinitiveListStatus::Locked])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $list, 'public_lists', 'definitive_list_lock', 'Lista definitiva bloqueada para atribuição futura.');

        return $list->refresh();
    }

    public function archive(DefinitiveList $list, User $actor): DefinitiveList
    {
        if ($this->definitiveStatus($list) === DefinitiveListStatus::Locked) {
            throw ValidationException::withMessages(['definitive_list' => 'Listas definitivas bloqueadas não devem ser arquivadas nesta sprint.']);
        }

        $list->forceFill(['status' => DefinitiveListStatus::Archived])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $list, 'public_lists', 'definitive_list_archive', 'Lista definitiva arquivada.');

        return $list->refresh();
    }

    private function hasPendingComplaints(ProvisionalList $list): bool
    {
        $finalStatuses = array_map(
            static fn (ComplaintStatus $status) => $status->value,
            array_filter(ComplaintStatus::cases(), static fn (ComplaintStatus $status) => $status->isFinal()),
        );

        return $list->complaints()
            ->whereNotIn('status', $finalStatuses)
            ->exists();
    }

    private function hasPendingHearings(ProvisionalList $list): bool
    {
        return $list->hearings()
            ->whereIn('status', [
                HearingStatus::Draft->value,
                HearingStatus::Issued->value,
                HearingStatus::Open->value,
                HearingStatus::Submitted->value,
                HearingStatus::UnderReview->value,
            ])
            ->exists();
    }

    private function generateListNumber(): string
    {
        $next = DefinitiveList::withTrashed()->count() + 1;

        do {
            $number = 'LD-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (DefinitiveList::withTrashed()->where('list_number', $number)->exists());

        return $number;
    }

    private function provisionalStatus(ProvisionalList $list): ?ProvisionalListStatus
    {
        $status = $list->getAttribute('status');

        if ($status instanceof ProvisionalListStatus) {
            return $status;
        }

        return is_string($status) ? ProvisionalListStatus::tryFrom($status) : null;
    }

    /**
     * @param  list<DefinitiveListStatus>  $statuses
     */
    private function definitiveStatusIsIn(DefinitiveList $list, array $statuses): bool
    {
        $status = $this->definitiveStatus($list);

        return $status !== null && in_array($status, $statuses, true);
    }

    private function definitiveStatus(DefinitiveList $list): ?DefinitiveListStatus
    {
        $status = $list->getAttribute('status');

        if ($status instanceof DefinitiveListStatus) {
            return $status;
        }

        return is_string($status) ? DefinitiveListStatus::tryFrom($status) : null;
    }

    /**
     * @param  list<ComplaintDecisionResult>  $results
     */
    private function complaintDecisionResultIsIn(ComplaintDecision $decision, array $results): bool
    {
        $result = $decision->getAttribute('decision_result');

        if (is_string($result)) {
            $result = ComplaintDecisionResult::tryFrom($result);
        }

        return $result instanceof ComplaintDecisionResult && in_array($result, $results, true);
    }

    private function entryStatusValue(ProvisionalListEntry $entry): string
    {
        $status = $entry->getAttribute('status');

        if ($status instanceof ListEntryStatus) {
            return $status->value;
        }

        return is_string($status) ? $status : '';
    }

    private function complaintDecisionSummary(ComplaintDecision $decision): string
    {
        $summary = $decision->getAttribute('summary');

        return is_string($summary) ? $summary : '';
    }

    private function complaintDecisionRequiresListUpdate(ComplaintDecision $decision): bool
    {
        return (bool) $decision->getAttribute('requires_list_update');
    }
}
