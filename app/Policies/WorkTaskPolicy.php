<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkTask;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Workflows\WorkTaskAssignmentService;

class WorkTaskPolicy
{
    use ChecksPermissions;

    private const MODULE = 'work_tasks';

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, WorkTask $task): bool
    {
        if (
            ! $this->viewAny($user)
            || ! $this->municipalScope->ownsWorkTask($user, $task)
        ) {
            return false;
        }

        if (
            $this->canAccess($user, self::MODULE, 'assign')
            || $this->hasReadOnlyAuditVisibility($user)
        ) {
            return true;
        }

        return $this->hasOperationalVisibility($user, $task);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'create');
    }

    public function claim(User $user, WorkTask $task): bool
    {
        return $task->isActive()
            && $this->canAccess($user, self::MODULE, 'claim')
            && $this->municipalScope->ownsWorkTask($user, $task)
            && ($task->assigned_user_id === null || $this->canAccess($user, self::MODULE, 'assign'))
            && ($task->municipal_team_id === null || $task->isInTeamOf($user) || $this->canAccess($user, self::MODULE, 'assign'));
    }

    public function reassign(User $user, WorkTask $task): bool
    {
        return $task->isActive()
            && $this->municipalScope->ownsWorkTask($user, $task)
            && (
                $this->canAccess($user, self::MODULE, 'assign')
                || (
                    $this->canAccess($user, self::MODULE, 'reassign')
                    && $this->hasOperationalVisibility($user, $task)
                )
            );
    }

    public function updateStatus(User $user, WorkTask $task): bool
    {
        return $task->isActive()
            && $this->canAccess($user, self::MODULE, 'update_status')
            && $this->municipalScope->ownsWorkTask($user, $task)
            && $this->canHandle($user, $task)
            && ($task->isAssignedTo($user) || $this->canAccess($user, self::MODULE, 'assign'));
    }

    public function complete(User $user, WorkTask $task): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $task->isActive()
            && $this->canAccess($user, self::MODULE, 'complete')
            && $this->municipalScope->ownsWorkTask($user, $task)
            && $this->canHandle($user, $task)
            && ($task->isAssignedTo($user) || $this->canAccess($user, self::MODULE, 'assign'));
    }

    public function cancel(User $user, WorkTask $task): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $task->isActive()
            && $this->canAccess($user, self::MODULE, 'cancel')
            && $this->municipalScope->ownsWorkTask($user, $task)
            && $this->view($user, $task);
    }

    public function audit(User $user, WorkTask $task): bool
    {
        return $this->view($user, $task) && $this->canAccess($user, self::MODULE, 'audit');
    }

    public function manageSla(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'manage_sla');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, WorkTask $task): bool
    {
        if (
            ! $this->viewAnyBackoffice($user)
            || ! $this->municipalScope->ownsWorkTask($user, $task)
        ) {
            return false;
        }

        if (
            $this->canAccess($user, self::MODULE, 'assign')
            || $this->hasReadOnlyAuditVisibility($user)
        ) {
            return true;
        }

        return $this->hasOperationalVisibility($user, $task);
    }

    private function canHandle(User $user, WorkTask $task): bool
    {
        return $this->canAccess($user, self::MODULE, 'assign')
            || app(WorkTaskAssignmentService::class)->canUserHandleTaskType($user, $task->type);
    }

    private function hasOperationalVisibility(User $user, WorkTask $task): bool
    {
        return $task->isAssignedTo($user)
            || (
                $this->canAccess($user, self::MODULE, 'view_team')
                && $task->isInTeamOf($user)
            );
    }

    private function hasReadOnlyAuditVisibility(User $user): bool
    {
        if (! $this->canAccess($user, self::MODULE, 'audit')) {
            return false;
        }

        foreach ([
            'assign',
            'claim',
            'reassign',
            'update_status',
            'complete',
            'cancel',
        ] as $mutation) {
            if ($this->canAccess($user, self::MODULE, $mutation)) {
                return false;
            }
        }

        return true;
    }
}
