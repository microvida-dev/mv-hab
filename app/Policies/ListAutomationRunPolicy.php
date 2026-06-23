<?php

namespace App\Policies;

use App\Models\ListAutomationRun;
use App\Models\User;

class ListAutomationRunPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermissionTo('public_lists', 'view');
    }

    public function view(User $user, ListAutomationRun $run): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('public_lists', 'create');
    }

    public function approve(User $user, ListAutomationRun $run): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('public_lists', 'approve');
    }
}
