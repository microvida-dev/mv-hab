<?php

namespace App\Services\Workflows;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Events\WorkTaskDueSoon;
use App\Events\WorkTaskOverdue;
use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskHistory;
use App\Models\WorkTaskSlaPolicy;
use App\Services\Audit\AuditTrailService;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class WorkTaskSlaService
{
    /**
     * @return array<string, array{label: string, business_days: int, warning_business_days: int}>
     */
    public function defaultPolicies(): array
    {
        return [
            WorkTask::TYPE_DOCUMENT_REVIEW => ['label' => WorkTask::typeLabel(WorkTask::TYPE_DOCUMENT_REVIEW), 'business_days' => 5, 'warning_business_days' => 1],
            WorkTask::TYPE_ELIGIBILITY_REVIEW => ['label' => WorkTask::typeLabel(WorkTask::TYPE_ELIGIBILITY_REVIEW), 'business_days' => 5, 'warning_business_days' => 1],
            WorkTask::TYPE_SCORING_REVIEW => ['label' => WorkTask::typeLabel(WorkTask::TYPE_SCORING_REVIEW), 'business_days' => 5, 'warning_business_days' => 1],
            WorkTask::TYPE_SUPPORT_TICKET => ['label' => WorkTask::typeLabel(WorkTask::TYPE_SUPPORT_TICKET), 'business_days' => 5, 'warning_business_days' => 1],
            WorkTask::TYPE_MAINTENANCE_TRIAGE => ['label' => WorkTask::typeLabel(WorkTask::TYPE_MAINTENANCE_TRIAGE), 'business_days' => 5, 'warning_business_days' => 1],
            WorkTask::TYPE_COMPLAINT_REVIEW => ['label' => WorkTask::typeLabel(WorkTask::TYPE_COMPLAINT_REVIEW), 'business_days' => 10, 'warning_business_days' => 2],
            WorkTask::TYPE_HEARING_REVIEW => ['label' => WorkTask::typeLabel(WorkTask::TYPE_HEARING_REVIEW), 'business_days' => 10, 'warning_business_days' => 2],
            WorkTask::TYPE_CONTRACT_REVIEW => ['label' => WorkTask::typeLabel(WorkTask::TYPE_CONTRACT_REVIEW), 'business_days' => 10, 'warning_business_days' => 2],
            WorkTask::TYPE_PAYMENT_REVIEW => ['label' => WorkTask::typeLabel(WorkTask::TYPE_PAYMENT_REVIEW), 'business_days' => 10, 'warning_business_days' => 2],
            WorkTask::TYPE_RENT_REVIEW => ['label' => WorkTask::typeLabel(WorkTask::TYPE_RENT_REVIEW), 'business_days' => 10, 'warning_business_days' => 2],
            WorkTask::TYPE_INSPECTION_SCHEDULE => ['label' => WorkTask::typeLabel(WorkTask::TYPE_INSPECTION_SCHEDULE), 'business_days' => 10, 'warning_business_days' => 2],
            WorkTask::TYPE_VISIT_SCHEDULE => ['label' => WorkTask::typeLabel(WorkTask::TYPE_VISIT_SCHEDULE), 'business_days' => 10, 'warning_business_days' => 2],
            WorkTask::TYPE_RGPD_REQUEST => ['label' => WorkTask::typeLabel(WorkTask::TYPE_RGPD_REQUEST), 'business_days' => 15, 'warning_business_days' => 3],
            WorkTask::TYPE_AUDIT_REVIEW => ['label' => WorkTask::typeLabel(WorkTask::TYPE_AUDIT_REVIEW), 'business_days' => 15, 'warning_business_days' => 3],
        ];
    }

    /** @return array{label: string, business_days: int, warning_business_days: int} */
    public function policyFor(string $type): array
    {
        $policy = WorkTaskSlaPolicy::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->first();

        if ($policy instanceof WorkTaskSlaPolicy) {
            return [
                'label' => $policy->label,
                'business_days' => $policy->business_days,
                'warning_business_days' => $policy->warning_business_days,
            ];
        }

        return $this->defaultPolicies()[$type] ?? [
            'label' => WorkTask::typeLabel($type),
            'business_days' => 5,
            'warning_business_days' => 1,
        ];
    }

    public function calculateDueAt(string $type, ?CarbonInterface $start = null): Carbon
    {
        $startDate = Carbon::instance($start ?? now());
        $remaining = (int) $this->policyFor($type)['business_days'];
        $dueAt = $startDate->copy();

        while ($remaining > 0) {
            $dueAt->addDay();

            if (! $dueAt->isWeekend()) {
                $remaining--;
            }
        }

        return $dueAt->setTimeFrom($startDate);
    }

    public function warningAt(WorkTask $task): ?Carbon
    {
        $dueAt = $task->getAttribute('due_at');

        if (! $dueAt instanceof DateTimeInterface) {
            return null;
        }

        $remaining = (int) $this->policyFor($task->type)['warning_business_days'];
        $warningAt = Carbon::instance($dueAt);

        while ($remaining > 0) {
            $warningAt->subDay();

            if (! $warningAt->isWeekend()) {
                $remaining--;
            }
        }

        return $warningAt;
    }

    public function isDueSoon(WorkTask $task, ?CarbonInterface $reference = null): bool
    {
        $warningAt = $this->warningAt($task);
        $dueAt = $task->getAttribute('due_at');

        if (! $dueAt instanceof DateTimeInterface) {
            return false;
        }

        $referenceAt = Carbon::instance($reference ?? now());

        return $warningAt !== null
            && $task->isActive()
            && $task->status !== WorkTask::STATUS_OVERDUE
            && $referenceAt->greaterThanOrEqualTo($warningAt)
            && ! $referenceAt->greaterThan($dueAt);
    }

    public function markOverdue(?User $actor = null): int
    {
        $count = 0;

        WorkTask::query()
            ->whereNotIn('status', [WorkTask::STATUS_COMPLETED, WorkTask::STATUS_CANCELLED, WorkTask::STATUS_OVERDUE])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->chunkById(100, function ($tasks) use (&$count, $actor): void {
                foreach ($tasks as $task) {
                    $previous = $task->status;
                    $dueAt = $task->getAttribute('due_at');
                    $task->forceFill([
                        'status' => WorkTask::STATUS_OVERDUE,
                        'updated_by' => $actor?->getKey(),
                    ])->save();

                    WorkTaskHistory::query()->create([
                        'work_task_id' => $task->id,
                        'event_code' => 'work_task_overdue',
                        'actor_id' => $actor?->id,
                        'from_status' => $previous,
                        'to_status' => WorkTask::STATUS_OVERDUE,
                        'from_team_id' => $task->municipal_team_id,
                        'to_team_id' => $task->municipal_team_id,
                        'from_user_id' => $task->assigned_user_id,
                        'to_user_id' => $task->assigned_user_id,
                        'note' => 'SLA ultrapassado.',
                        'metadata' => ['due_at' => $dueAt instanceof DateTimeInterface ? $dueAt->format(DATE_ATOM) : null],
                        'occurred_at' => now(),
                    ]);

                    app(AuditTrailService::class)->record(
                        eventCode: 'work_task_overdue',
                        auditable: $task,
                        category: AuditEventCategory::Workflow,
                        severity: AuditEventSeverity::Warning,
                        description: 'Tarefa marcada como vencida por SLA.',
                        oldValues: ['status' => $previous],
                        newValues: ['status' => WorkTask::STATUS_OVERDUE],
                        metadata: ['task_id' => $task->id],
                        actor: $actor,
                    );

                    WorkTaskOverdue::dispatch($task->id, $actor?->id);
                    $count++;
                }
            });

        return $count;
    }

    public function dispatchDueSoonEvents(?User $actor = null): int
    {
        $count = 0;

        WorkTask::query()
            ->whereNotIn('status', [WorkTask::STATUS_COMPLETED, WorkTask::STATUS_CANCELLED, WorkTask::STATUS_OVERDUE])
            ->whereNotNull('due_at')
            ->chunkById(100, function ($tasks) use (&$count, $actor): void {
                foreach ($tasks as $task) {
                    if ($this->isDueSoon($task)) {
                        WorkTaskDueSoon::dispatch($task->id, $actor?->id);
                        $count++;
                    }
                }
            });

        return $count;
    }
}
