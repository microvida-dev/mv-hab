<?php

namespace App\Services\ListAutomation;

use App\Enums\ListAutomationStatus;
use App\Enums\ListAutomationType;
use App\Models\Contest;
use App\Models\ListAutomationRun;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListAutomationRunService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(Contest $contest, ListAutomationType $type, User $actor, array $payload): ListAutomationRun
    {
        $run = new ListAutomationRun([
            'criteria_snapshot' => $payload['criteria_snapshot'] ?? [],
            'result_payload' => $payload['result_payload'] ?? [],
        ]);
        $run->forceFill([
            'run_number' => $this->number(),
            'contest_id' => $contest->id,
            'type' => $type,
            'status' => $payload['status'] ?? ListAutomationStatus::Generated,
            'source_ranking_snapshot_id' => $payload['source_ranking_snapshot_id'] ?? null,
            'source_provisional_list_id' => $payload['source_provisional_list_id'] ?? null,
            'source_definitive_list_id' => $payload['source_definitive_list_id'] ?? null,
            'total_candidates' => $payload['total_candidates'] ?? 0,
            'included_count' => $payload['included_count'] ?? 0,
            'excluded_count' => $payload['excluded_count'] ?? 0,
            'warnings_count' => count($payload['warnings'] ?? []),
            'generated_by' => $actor->id,
            'generated_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $run, 'public_lists', 'list_automation_run_create', 'Execução de automação de lista registada.');

        return $run->refresh();
    }

    public function approve(ListAutomationRun $run, User $actor): ListAutomationRun
    {
        return DB::transaction(function () use ($run, $actor): ListAutomationRun {
            $run = ListAutomationRun::query()->lockForUpdate()->findOrFail($run->id);

            if ($this->status($run) === ListAutomationStatus::Approved) {
                return $run;
            }

            if (! $this->statusIsIn($run, [ListAutomationStatus::Generated, ListAutomationStatus::UnderReview])) {
                throw ValidationException::withMessages(['list_automation_run' => 'A automação não se encontra num estado aprovável.']);
            }

            $run->forceFill([
                'status' => ListAutomationStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ])->save();

            $this->auditLogger->record(AuditEvents::APPROVE, $run, 'public_lists', 'list_automation_run_approve', 'Automação de lista aprovada.');

            return $run->refresh();
        });
    }

    private function number(): string
    {
        $next = ListAutomationRun::withTrashed()->count() + 1;

        do {
            $number = 'AUTO-LST-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (ListAutomationRun::withTrashed()->where('run_number', $number)->exists());

        return $number;
    }

    /**
     * @param  list<ListAutomationStatus>  $statuses
     */
    private function statusIsIn(ListAutomationRun $run, array $statuses): bool
    {
        $status = $this->status($run);

        return $status !== null && in_array($status, $statuses, true);
    }

    private function status(ListAutomationRun $run): ?ListAutomationStatus
    {
        $status = $run->getAttribute('status');

        if ($status instanceof ListAutomationStatus) {
            return $status;
        }

        return is_string($status) ? ListAutomationStatus::tryFrom($status) : null;
    }
}
