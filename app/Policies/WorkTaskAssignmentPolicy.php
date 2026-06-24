<?php

namespace App\Policies;

use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTask;
use App\Services\Workflows\WorkTaskAssignmentService;

class WorkTaskAssignmentPolicy
{
    public function assign(User $actor, WorkTask $task, ?MunicipalTeam $team = null, ?User $assignee = null): bool
    {
        if (! app(WorkTaskPolicy::class)->reassign($actor, $task)) {
            return false;
        }

        if ($team instanceof MunicipalTeam && ! $team->isActive()) {
            return false;
        }

        if (! $assignee instanceof User) {
            return true;
        }

        $assignment = app(WorkTaskAssignmentService::class);

        return $assignment->isActiveUser($assignee)
            && $assignment->canUserHandleTaskType($assignee, $task->type)
            && (! $team instanceof MunicipalTeam || $assignment->belongsToActiveTeam($assignee, $team));
    }
}
