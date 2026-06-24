<?php

namespace App\Services\Workflows;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Events\WorkTaskCreated;
use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskHistory;
use App\Services\Audit\AuditTrailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkTaskCreationService
{
    public function __construct(
        private readonly WorkTaskAssignmentService $assignmentService,
        private readonly WorkTaskSlaService $slaService,
        private readonly AuditTrailService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function createFromSource(
        string $type,
        ?Model $related = null,
        ?User $actor = null,
        ?string $source = null,
        string $priority = WorkTask::PRIORITY_NORMAL,
        array $metadata = [],
    ): WorkTask {
        return DB::transaction(function () use ($type, $related, $actor, $source, $priority, $metadata): WorkTask {
            $existing = $this->activeDuplicate($type, $related, $source);

            if ($existing instanceof WorkTask) {
                return $existing;
            }

            $dueAt = $this->slaService->calculateDueAt($type);
            $relatedType = $related instanceof Model ? $related->getMorphClass() : null;
            $relatedId = $related instanceof Model ? $related->getKey() : null;

            $task = WorkTask::query()->create([
                'task_number' => $this->number(),
                'type' => $type,
                'source' => $source,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'priority' => $priority,
                'status' => WorkTask::STATUS_PENDING,
                'due_at' => $dueAt,
                'metadata' => $this->minimizeMetadata($metadata),
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);

            WorkTaskHistory::query()->create([
                'work_task_id' => $task->id,
                'event_code' => 'work_task_created',
                'actor_id' => $actor?->id,
                'to_status' => WorkTask::STATUS_PENDING,
                'note' => 'Tarefa criada a partir de ponto de integração operacional.',
                'metadata' => ['source' => $source],
                'occurred_at' => now(),
            ]);

            $this->audit->record(
                eventCode: 'work_task_created',
                auditable: $task,
                category: AuditEventCategory::Workflow,
                severity: AuditEventSeverity::Info,
                description: 'Tarefa operacional criada.',
                newValues: [
                    'type' => $task->type,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'due_at' => $dueAt->toIso8601String(),
                ],
                metadata: [
                    'task_id' => $task->id,
                    'source' => $source,
                    'related_type' => $relatedType,
                    'related_id' => $relatedId,
                ],
                actor: $actor,
            );

            WorkTaskCreated::dispatch($task->id, $actor?->id);

            return $this->assignmentService->assignByCompetency($task, $actor);
        });
    }

    private function activeDuplicate(string $type, ?Model $related, ?string $source): ?WorkTask
    {
        $query = WorkTask::query()
            ->where('type', $type)
            ->where('source', $source)
            ->whereNotIn('status', [WorkTask::STATUS_COMPLETED, WorkTask::STATUS_CANCELLED]);

        if ($related instanceof Model) {
            $query->where('related_type', $related->getMorphClass())
                ->where('related_id', $related->getKey());
        } else {
            $query->whereNull('related_type')->whereNull('related_id');
        }

        return $query->first();
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function minimizeMetadata(array $metadata): array
    {
        return collect($metadata)
            ->reject(fn (mixed $value, string $key): bool => in_array(strtolower($key), ['password', 'token', 'secret', 'nif', 'document_path', 'storage_path'], true))
            ->all();
    }

    private function number(): string
    {
        return 'WTK-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
    }
}
