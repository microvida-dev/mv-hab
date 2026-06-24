<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkTask;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Workflows\WorkTaskAssignmentService;

class WorkTaskPolicy
{
    use ChecksPermissions;

    private const MODULE = 'work_tasks';

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, WorkTask $task): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($user->hasRole(['administrator', 'auditor']) || $this->canAccess($user, self::MODULE, 'assign')) {
            return true;
        }

        if ($task->isAssignedTo($user)) {
            return true;
        }

        return $this->canAccess($user, self::MODULE, 'view_team') && $task->isInTeamOf($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'create');
    }

    public function claim(User $user, WorkTask $task): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $task->isActive()
            && $this->canAccess($user, self::MODULE, 'claim')
            && ($task->assigned_user_id === null || $this->canAccess($user, self::MODULE, 'assign'))
            && ($task->municipal_team_id === null || $task->isInTeamOf($user) || $this->canAccess($user, self::MODULE, 'assign'));
    }

    public function reassign(User $user, WorkTask $task): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $task->isActive()
            && ($this->canAccess($user, self::MODULE, 'assign') || ($this->canAccess($user, self::MODULE, 'reassign') && $this->view($user, $task)));
    }

    public function updateStatus(User $user, WorkTask $task): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $task->isActive()
            && $this->canAccess($user, self::MODULE, 'update_status')
            && $this->canHandle($user, $task)
            && ($task->isAssignedTo($user) || $this->canAccess($user, self::MODULE, 'assign'));
    }

    public function complete(User $user, WorkTask $task): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $task->isActive()
            && $this->canAccess($user, self::MODULE, 'complete')
            && $this->canHandle($user, $task)
            && ($task->isAssignedTo($user) || $this->canAccess($user, self::MODULE, 'assign'));
    }

    public function cancel(User $user, WorkTask $task): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $task->isActive()
            && $this->canAccess($user, self::MODULE, 'cancel')
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

    private function canHandle(User $user, WorkTask $task): bool
    {
        return $this->canAccess($user, self::MODULE, 'assign')
            || app(WorkTaskAssignmentService::class)->canUserHandleTaskType($user, $task->type);
    }
}
