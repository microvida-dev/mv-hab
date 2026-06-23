<?php

namespace App\Policies;

use App\Models\ProcedureTemplate;
use App\Models\User;

class ProcedureTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermissionTo('documents', 'view');
    }

    public function view(User $user, ProcedureTemplate $template): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('documents', 'create');
    }

    public function update(User $user, ProcedureTemplate $template): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('documents', 'update');
    }

    public function publish(User $user, ProcedureTemplate $template): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('documents', 'publish');
    }
}
