<?php

namespace App\Policies;

use App\Models\IndicatorDefinition;
use App\Models\User;

class IndicatorDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission('reports.view');
    }

    public function view(User $user, IndicatorDefinition $indicator): bool
    {
        return $this->viewAny($user) && (! $indicator->required_permission || $user->hasPermission($indicator->required_permission));
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('reports.manage');
    }

    public function update(User $user, IndicatorDefinition $indicator): bool
    {
        return $user->hasPermission('reports.manage');
    }

    public function delete(User $user, IndicatorDefinition $indicator): bool
    {
        return $user->hasPermission('reports.manage');
    }
}
