<?php

namespace App\Services\Administrative;

use App\Enums\AdministrativeTaskStatus;
use App\Models\AdministrativeProcess;
use App\Models\AdministrativeTask;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class AdministrativeTaskService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(AdministrativeProcess $process, array $data, User $actor): AdministrativeTask
    {
        $task = new AdministrativeTask([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'due_at' => $data['due_at'] ?? null,
        ]);
        $task->forceFill([
            'administrative_process_id' => $process->id,
            'application_id' => $process->application_id,
            'status' => AdministrativeTaskStatus::Open,
            'created_by' => $actor->id,
        ]);
        $task->save();

        $this->auditLogger->record(AuditEvents::CREATE, $task, 'administrative_processes', 'task_create', 'Tarefa administrativa criada.');

        return $task->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AdministrativeTask $task, array $data, User $actor): AdministrativeTask
    {
        $task->fill($data)->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $task, 'administrative_processes', 'task_update', 'Tarefa administrativa atualizada.');

        return $task->refresh();
    }

    public function complete(AdministrativeTask $task, User $actor): AdministrativeTask
    {
        $task->forceFill(['status' => AdministrativeTaskStatus::Completed, 'completed_at' => now()])->save();

        return $task->refresh();
    }

    public function cancel(AdministrativeTask $task, User $actor): AdministrativeTask
    {
        $task->forceFill(['status' => AdministrativeTaskStatus::Cancelled])->save();

        return $task->refresh();
    }
}
