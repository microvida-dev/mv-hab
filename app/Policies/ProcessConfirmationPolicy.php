<?php

namespace App\Policies;

use App\Models\ProcessConfirmation;
use App\Models\User;

class ProcessConfirmationPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermissionTo('applications', 'view');
    }

    public function view(User $user, ProcessConfirmation $confirmation): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && ($user->hasPermissionTo('applications', 'update') || $user->hasPermissionTo('applications', 'approve'));
    }

    public function send(User $user, ProcessConfirmation $confirmation): bool
    {
        return $this->create($user);
    }
}
