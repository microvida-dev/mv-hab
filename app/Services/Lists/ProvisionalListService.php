<?php

namespace App\Services\Lists;

use App\Enums\ListPublicationChannel;
use App\Enums\ProvisionalListStatus;
use App\Enums\RankingSnapshotStatus;
use App\Models\ProvisionalList;
use App\Models\RankingSnapshot;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProvisionalListService
{
    public function __construct(
        private readonly ListEntryBuilderService $entryBuilder,
        private readonly ListPublicationService $publicationService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function generateFromSnapshot(array $data, User $actor): ProvisionalList
    {
        $snapshot = RankingSnapshot::query()->with(['scoringRun', 'entries.application.user', 'entries.applicationScore'])->findOrFail($data['ranking_snapshot_id']);

        if (! in_array($snapshot->status, [RankingSnapshotStatus::Internal, RankingSnapshotStatus::Locked], true)) {
            throw ValidationException::withMessages(['ranking_snapshot_id' => 'A lista só pode ser gerada a partir de snapshot interno ou bloqueado.']);
        }

        if ($snapshot->entries->isEmpty()) {
            throw ValidationException::withMessages(['ranking_snapshot_id' => 'O snapshot não tem entradas de ranking.']);
        }

        return DB::transaction(function () use ($snapshot, $data, $actor) {
            $list = new ProvisionalList([
                'ranking_snapshot_id' => $snapshot->id,
                'scoring_run_id' => $snapshot->scoring_run_id,
                'program_id' => $snapshot->program_id,
                'contest_id' => $snapshot->contest_id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'publication_starts_at' => $data['publication_starts_at'] ?? null,
                'publication_ends_at' => $data['publication_ends_at'] ?? null,
                'complaint_period_starts_at' => $data['complaint_period_starts_at'] ?? null,
                'complaint_period_ends_at' => $data['complaint_period_ends_at'] ?? null,
                'anonymization_mode' => $data['anonymization_mode'],
                'public_visibility' => (bool) ($data['public_visibility'] ?? false),
                'legal_basis' => $data['legal_basis'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);
            $list->forceFill([
                'list_number' => $this->generateListNumber(),
                'status' => ProvisionalListStatus::Draft,
                'version_number' => 1,
                'generated_by' => $actor->id,
                'generated_at' => now(),
            ])->save();

            foreach ($snapshot->entries as $entry) {
                $this->entryBuilder->createFromRankingEntry($list, $entry);
            }

            $this->auditLogger->record(
                AuditEvents::CREATE,
                $list,
                'public_lists',
                'provisional_list_generate',
                'Lista provisória gerada a partir de snapshot de ranking.',
                metadata: ['ranking_snapshot_id' => $snapshot->id, 'entries_count' => $snapshot->entries->count()],
            );

            $list->refresh();

            return $list->load(['entries', 'rankingSnapshot']);
        });
    }

    public function sendToReview(ProvisionalList $list, User $actor): ProvisionalList
    {
        if ($this->provisionalStatus($list) !== ProvisionalListStatus::Draft) {
            throw ValidationException::withMessages(['provisional_list' => 'Apenas listas em rascunho podem seguir para revisão.']);
        }

        $list->forceFill([
            'status' => ProvisionalListStatus::UnderReview,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $list, 'public_lists', 'provisional_list_review', 'Lista provisória enviada para revisão.');

        return $list->refresh();
    }

    public function approve(ProvisionalList $list, User $actor): ProvisionalList
    {
        if (! $this->provisionalStatusIsIn($list, [ProvisionalListStatus::Draft, ProvisionalListStatus::UnderReview])) {
            throw ValidationException::withMessages(['provisional_list' => 'A lista provisória não está num estado aprovável.']);
        }

        $list->forceFill([
            'status' => ProvisionalListStatus::Approved,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::APPROVE, $list, 'public_lists', 'provisional_list_approve', 'Lista provisória aprovada.');

        return $list->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function publish(ProvisionalList $list, User $actor, array $data = []): ProvisionalList
    {
        $data['channel'] ??= $list->public_visibility ? ListPublicationChannel::PublicPortal : ListPublicationChannel::CandidateArea;
        $data['public_url'] ??= $list->public_visibility ? route('public.results.show', ['listPublication' => 'pending'], false) : null;

        $publication = $this->publicationService->publishProvisional($list, $actor, $data);

        if ($list->public_visibility) {
            $publication->forceFill(['public_url' => route('public.results.show', $publication, false)])->save();
        }

        return $list->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function openComplaintPeriod(ProvisionalList $list, User $actor, array $data = []): ProvisionalList
    {
        if (! $this->provisionalStatusIsIn($list, [ProvisionalListStatus::Published, ProvisionalListStatus::ComplaintPeriodClosed])) {
            throw ValidationException::withMessages(['provisional_list' => 'A lista deve estar publicada antes de abrir reclamações.']);
        }

        $list->forceFill([
            'status' => ProvisionalListStatus::ComplaintPeriodOpen,
            'complaint_period_starts_at' => $data['complaint_period_starts_at'] ?? $list->complaint_period_starts_at ?? now(),
            'complaint_period_ends_at' => $data['complaint_period_ends_at'] ?? $list->complaint_period_ends_at,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $list, 'public_lists', 'provisional_list_open_complaints', 'Prazo de reclamação aberto.');

        return $list->refresh();
    }

    public function closeComplaintPeriod(ProvisionalList $list, User $actor): ProvisionalList
    {
        if ($this->provisionalStatus($list) !== ProvisionalListStatus::ComplaintPeriodOpen) {
            throw ValidationException::withMessages(['provisional_list' => 'A lista não tem prazo de reclamação aberto.']);
        }

        $list->forceFill([
            'status' => ProvisionalListStatus::ComplaintPeriodClosed,
            'complaint_period_ends_at' => $list->complaint_period_ends_at ?? now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $list, 'public_lists', 'provisional_list_close_complaints', 'Prazo de reclamação fechado.');

        return $list->refresh();
    }

    public function cancel(ProvisionalList $list, User $actor): ProvisionalList
    {
        if ($this->provisionalStatusIsIn($list, [ProvisionalListStatus::Published, ProvisionalListStatus::ComplaintPeriodOpen, ProvisionalListStatus::ComplaintPeriodClosed])) {
            throw ValidationException::withMessages(['provisional_list' => 'Listas publicadas não devem ser canceladas sem procedimento formal de substituição.']);
        }

        $list->forceFill(['status' => ProvisionalListStatus::Cancelled])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $list, 'public_lists', 'provisional_list_cancel', 'Lista provisória cancelada.');

        return $list->refresh();
    }

    public function archive(ProvisionalList $list, User $actor): ProvisionalList
    {
        $list->forceFill(['status' => ProvisionalListStatus::Archived])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $list, 'public_lists', 'provisional_list_archive', 'Lista provisória arquivada.');

        return $list->refresh();
    }

    private function generateListNumber(): string
    {
        $next = ProvisionalList::withTrashed()->count() + 1;

        do {
            $number = 'LP-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (ProvisionalList::withTrashed()->where('list_number', $number)->exists());

        return $number;
    }

    /**
     * @param  list<ProvisionalListStatus>  $statuses
     */
    private function provisionalStatusIsIn(ProvisionalList $list, array $statuses): bool
    {
        $status = $this->provisionalStatus($list);

        return $status !== null && in_array($status, $statuses, true);
    }

    private function provisionalStatus(ProvisionalList $list): ?ProvisionalListStatus
    {
        $status = $list->getAttribute('status');

        if ($status instanceof ProvisionalListStatus) {
            return $status;
        }

        return is_string($status) ? ProvisionalListStatus::tryFrom($status) : null;
    }
}
