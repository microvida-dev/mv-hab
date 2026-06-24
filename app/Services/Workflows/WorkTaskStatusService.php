<?php

namespace App\Services\Workflows;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Events\WorkTaskCancelled;
use App\Events\WorkTaskCompleted;
use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskHistory;
use App\Services\Audit\AuditTrailService;
use DomainException;

class WorkTaskStatusService
{
    private const WAITING_STATUSES = [
        WorkTask::STATUS_WAITING_CANDIDATE,
        WorkTask::STATUS_WAITING_INTERNAL,
        WorkTask::STATUS_WAITING_EXTERNAL,
    ];

    public function __construct(private readonly AuditTrailService $audit) {}

    public function start(WorkTask $task, User $actor, ?string $note = null): WorkTask
    {
        return $this->transition($task, $actor, WorkTask::STATUS_IN_ANALYSIS, 'work_task_started', $note ?? 'Tarefa colocada em análise.');
    }

    public function wait(WorkTask $task, User $actor, string $status, string $note): WorkTask
    {
        if (! in_array($status, self::WAITING_STATUSES, true)) {
            throw new DomainException('Estado de espera inválido.');
        }

        return $this->transition($task, $actor, $status, 'work_task_waiting', $note);
    }

    public function complete(WorkTask $task, User $actor, string $outcomeNote): WorkTask
    {
        if (trim($outcomeNote) === '') {
            throw new DomainException('A conclusão exige nota ou resultado.');
        }

        $updated = $this->transition($task, $actor, WorkTask::STATUS_COMPLETED, 'work_task_completed', $outcomeNote, [
            'completed_at' => now(),
            'outcome_note' => $outcomeNote,
        ]);

        WorkTaskCompleted::dispatch($updated->id, $actor->id);

        return $updated;
    }

    public function cancel(WorkTask $task, User $actor, string $reason): WorkTask
    {
        if (trim($reason) === '') {
            throw new DomainException('O cancelamento exige motivo.');
        }

        $updated = $this->transition($task, $actor, WorkTask::STATUS_CANCELLED, 'work_task_cancelled', $reason, [
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        WorkTaskCancelled::dispatch($updated->id, $actor->id);

        return $updated;
    }

    /**
     * @param  array<string, mixed>  $extraValues
     */
    private function transition(WorkTask $task, User $actor, string $status, string $eventCode, string $note, array $extraValues = []): WorkTask
    {
        if (! $task->isActive()) {
            throw new DomainException('A tarefa já não está ativa.');
        }

        $previous = [
            'status' => $task->status,
            'municipal_team_id' => $task->municipal_team_id,
            'assigned_user_id' => $task->assigned_user_id,
        ];

        $task->forceFill($extraValues + [
            'status' => $status,
            'updated_by' => $actor->id,
        ])->save();

        WorkTaskHistory::query()->create([
            'work_task_id' => $task->id,
            'event_code' => $eventCode,
            'actor_id' => $actor->id,
            'from_status' => $previous['status'],
            'to_status' => $status,
            'from_team_id' => $previous['municipal_team_id'],
            'to_team_id' => $task->municipal_team_id,
            'from_user_id' => $previous['assigned_user_id'],
            'to_user_id' => $task->assigned_user_id,
            'note' => $note,
            'metadata' => [],
            'occurred_at' => now(),
        ]);

        $this->audit->record(
            eventCode: $eventCode,
            auditable: $task,
            category: AuditEventCategory::Workflow,
            severity: $status === WorkTask::STATUS_CANCELLED ? AuditEventSeverity::Warning : AuditEventSeverity::Notice,
            description: $note,
            oldValues: ['status' => $previous['status']],
            newValues: ['status' => $status],
            metadata: ['task_id' => $task->id],
            actor: $actor,
        );

        return $task->refresh();
    }
}
