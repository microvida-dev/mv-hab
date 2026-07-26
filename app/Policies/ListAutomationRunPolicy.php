<?php

namespace App\Policies;

use App\Models\ListAutomationRun;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ListAutomationRunPolicy
{
    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

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

    public function viewBackoffice(User $user, ListAutomationRun $run): bool
    {
        return ! $user->hasRole('candidate')
            && $user->hasPermissionTo('public_lists', 'view')
            && $this->municipalScope->ownsListAutomationRun($user, $run);
    }

    public function approveBackoffice(User $user, ListAutomationRun $run): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $user->hasPermissionTo('public_lists', 'approve')
            && $this->municipalScope->ownsListAutomationRun($user, $run);
    }
}
