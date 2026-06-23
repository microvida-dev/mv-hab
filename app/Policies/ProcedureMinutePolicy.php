<?php

namespace App\Policies;

use App\Models\ProcedureMinute;
use App\Models\User;

class ProcedureMinutePolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermissionTo('documents', 'view');
    }

    public function view(User $user, ProcedureMinute $minute): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('documents', 'create');
    }

    public function approve(User $user, ProcedureMinute $minute): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('documents', 'approve');
    }

    public function download(User $user, ProcedureMinute $minute): bool
    {
        return $this->view($user, $minute);
    }
}
