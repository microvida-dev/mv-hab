<?php

namespace App\Services\Workflows;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Events\WorkTaskAssigned;
use App\Events\WorkTaskReassigned;
use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskHistory;
use App\Services\Audit\AuditTrailService;
use DomainException;

class WorkTaskAssignmentService
{
    private const ACTIVE_STATUSES = [
        WorkTask::STATUS_PENDING,
        WorkTask::STATUS_ASSIGNED,
        WorkTask::STATUS_IN_ANALYSIS,
        WorkTask::STATUS_WAITING_CANDIDATE,
        WorkTask::STATUS_WAITING_INTERNAL,
        WorkTask::STATUS_WAITING_EXTERNAL,
        WorkTask::STATUS_OVERDUE,
    ];

    /**
     * @return array<string, array{teams: list<string>, roles: list<string>}>
     */
    public function matrix(): array
    {
        return [
            WorkTask::TYPE_DOCUMENT_REVIEW => ['teams' => ['Gabinete Técnico'], 'roles' => ['municipal_technician']],
            WorkTask::TYPE_ELIGIBILITY_REVIEW => ['teams' => ['Gabinete Técnico'], 'roles' => ['municipal_technician', 'jury']],
            WorkTask::TYPE_SCORING_REVIEW => ['teams' => ['Gabinete Técnico'], 'roles' => ['municipal_technician', 'jury']],
            WorkTask::TYPE_COMPLAINT_REVIEW => ['teams' => ['Gabinete Jurídico'], 'roles' => ['legal_manager', 'jury']],
            WorkTask::TYPE_HEARING_REVIEW => ['teams' => ['Gabinete Jurídico'], 'roles' => ['legal_manager']],
            WorkTask::TYPE_CONTRACT_REVIEW => ['teams' => ['Gabinete Jurídico', 'Gabinete de Habitação'], 'roles' => ['legal_manager', 'housing_manager']],
            WorkTask::TYPE_RENT_REVIEW => ['teams' => ['Gabinete Financeiro'], 'roles' => ['financial_manager']],
            WorkTask::TYPE_PAYMENT_REVIEW => ['teams' => ['Gabinete Financeiro'], 'roles' => ['financial_manager']],
            WorkTask::TYPE_MAINTENANCE_TRIAGE => ['teams' => ['Manutenção'], 'roles' => ['maintenance_manager']],
            WorkTask::TYPE_INSPECTION_SCHEDULE => ['teams' => ['Vistorias'], 'roles' => ['inspection_manager']],
            WorkTask::TYPE_VISIT_SCHEDULE => ['teams' => ['Atendimento', 'Gabinete de Habitação'], 'roles' => ['support_agent', 'housing_manager']],
            WorkTask::TYPE_SUPPORT_TICKET => ['teams' => ['Atendimento'], 'roles' => ['support_agent']],
            WorkTask::TYPE_RGPD_REQUEST => ['teams' => ['Auditoria'], 'roles' => ['auditor', 'administrator']],
            WorkTask::TYPE_AUDIT_REVIEW => ['teams' => ['Auditoria'], 'roles' => ['auditor']],
        ];
    }

    public function assignByCompetency(WorkTask $task, ?User $actor = null): WorkTask
    {
        $team = $this->activeTeamFor($task->type);
        $assignee = $team instanceof MunicipalTeam ? $this->leastLoadedActiveUser($task->type, $team) : null;

        return $this->assign(
            task: $task,
            actor: $actor,
            team: $team,
            assignee: $assignee,
            reason: 'Atribuição automática por competência.',
            eventCode: 'work_task_assigned',
        );
    }

    public function claim(WorkTask $task, User $actor): WorkTask
    {
        if (! $task->isActive()) {
            throw new DomainException('A tarefa já não está ativa.');
        }

        if (! $this->canUserHandleTaskType($actor, $task->type)) {
            throw new DomainException('O utilizador não tem perfil compatível com esta tarefa.');
        }

        if ($task->municipal_team_id !== null && ! $task->isInTeamOf($actor) && ! $actor->hasPermission('work_tasks.assign')) {
            throw new DomainException('O utilizador não pertence à equipa competente.');
        }

        return $this->assign(
            task: $task,
            actor: $actor,
            team: $task->municipalTeam,
            assignee: $actor,
            reason: 'Tarefa assumida pelo técnico.',
            eventCode: 'work_task_claimed',
        );
    }

    public function reassign(WorkTask $task, User $actor, ?MunicipalTeam $team, ?User $assignee, string $reason): WorkTask
    {
        if (trim($reason) === '') {
            throw new DomainException('A reatribuição exige justificação.');
        }

        if (! $task->isActive()) {
            throw new DomainException('A tarefa já não está ativa.');
        }

        if ($team instanceof MunicipalTeam && ! $team->isActive()) {
            throw new DomainException('A equipa de destino está inativa.');
        }

        if ($assignee instanceof User) {
            if (! $this->isActiveUser($assignee)) {
                throw new DomainException('O utilizador de destino está inativo.');
            }

            if (! $this->canUserHandleTaskType($assignee, $task->type)) {
                throw new DomainException('O utilizador de destino não tem perfil compatível.');
            }

            if ($team instanceof MunicipalTeam && ! $this->belongsToActiveTeam($assignee, $team)) {
                throw new DomainException('O utilizador de destino não pertence à equipa de destino.');
            }
        }

        return $this->assign($task, $actor, $team, $assignee, $reason, 'work_task_reassigned');
    }

    public function canUserHandleTaskType(User $user, string $type): bool
    {
        $roles = $this->matrix()[$type]['roles'] ?? [];

        return $user->hasRole('administrator') || $user->hasRole($roles);
    }

    public function activeTeamFor(string $type): ?MunicipalTeam
    {
        $teamNames = $this->matrix()[$type]['teams'] ?? [];

        foreach ($teamNames as $teamName) {
            $team = MunicipalTeam::query()
                ->where('name', $teamName)
                ->where('status', 'active')
                ->first();

            if ($team instanceof MunicipalTeam) {
                return $team;
            }
        }

        return null;
    }

    public function leastLoadedActiveUser(string $type, MunicipalTeam $team): ?User
    {
        $roles = $this->matrix()[$type]['roles'] ?? [];

        return $team->members()
            ->wherePivotNull('left_at')
            ->whereNull('deactivated_at')
            ->where(function ($query): void {
                $query->whereNull('users.status')->orWhere('users.status', 'active');
            })
            ->whereHas('roles', function ($query) use ($roles): void {
                $query->whereIn('name', $roles)->orWhere('name', 'administrator');
            })
            ->withCount(['assignedWorkTasks as active_work_tasks_count' => function ($query): void {
                $query->whereIn('status', self::ACTIVE_STATUSES);
            }])
            ->orderBy('active_work_tasks_count')
            ->orderBy('users.name')
            ->first();
    }

    public function belongsToActiveTeam(User $user, MunicipalTeam $team): bool
    {
        return $user->municipalTeams()
            ->where('municipal_teams.id', $team->id)
            ->where('municipal_teams.status', 'active')
            ->wherePivotNull('left_at')
            ->exists();
    }

    public function isActiveUser(User $user): bool
    {
        $status = $user->status ?: 'active';

        return $user->deactivated_at === null
            && $status === 'active';
    }

    private function assign(
        WorkTask $task,
        ?User $actor,
        ?MunicipalTeam $team,
        ?User $assignee,
        string $reason,
        string $eventCode,
    ): WorkTask {
        $oldValues = [
            'status' => $task->status,
            'municipal_team_id' => $task->municipal_team_id,
            'assigned_user_id' => $task->assigned_user_id,
        ];

        $newStatus = $assignee instanceof User || $team instanceof MunicipalTeam
            ? WorkTask::STATUS_ASSIGNED
            : WorkTask::STATUS_PENDING;

        $task->forceFill([
            'status' => $newStatus,
            'municipal_team_id' => $team?->id,
            'assigned_user_id' => $assignee?->id,
            'assigned_at' => $assignee instanceof User ? now() : $task->assigned_at,
            'reassignment_reason' => $eventCode === 'work_task_reassigned' ? $reason : $task->reassignment_reason,
            'updated_by' => $actor?->id,
        ])->save();

        WorkTaskHistory::query()->create([
            'work_task_id' => $task->id,
            'event_code' => $eventCode,
            'actor_id' => $actor?->id,
            'from_status' => $oldValues['status'],
            'to_status' => $task->status,
            'from_team_id' => $oldValues['municipal_team_id'],
            'to_team_id' => $task->municipal_team_id,
            'from_user_id' => $oldValues['assigned_user_id'],
            'to_user_id' => $task->assigned_user_id,
            'note' => $reason,
            'metadata' => [],
            'occurred_at' => now(),
        ]);

        app(AuditTrailService::class)->record(
            eventCode: $eventCode,
            auditable: $task,
            category: AuditEventCategory::Workflow,
            severity: AuditEventSeverity::Notice,
            description: $reason,
            oldValues: $oldValues,
            newValues: [
                'status' => $task->status,
                'municipal_team_id' => $task->municipal_team_id,
                'assigned_user_id' => $task->assigned_user_id,
            ],
            metadata: ['task_id' => $task->id],
            actor: $actor,
        );

        if ($eventCode === 'work_task_reassigned') {
            WorkTaskReassigned::dispatch($task->id, $actor?->id);
        } else {
            WorkTaskAssigned::dispatch($task->id, $actor?->id);
        }

        return $task->refresh();
    }
}
