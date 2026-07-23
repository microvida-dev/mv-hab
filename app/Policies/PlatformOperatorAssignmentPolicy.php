<?php

namespace App\Policies;

use App\Models\PlatformOperatorAssignment;
use App\Models\User;
use App\Services\Platform\PlatformOperatorScopeService;

final class PlatformOperatorAssignmentPolicy
{
    public function __construct(
        private readonly PlatformOperatorScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->scope->hasGlobalScope($user)
            && $user->hasPermission('platform_operators.view');
    }

    public function view(User $user, PlatformOperatorAssignment $assignment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function revoke(User $user, PlatformOperatorAssignment $assignment): bool
    {
        return $this->canManage($user);
    }

    public function auditAny(User $user): bool
    {
        return $this->scope->hasGlobalScope($user)
            && $user->hasPermission('platform_operators.audit');
    }

    private function canManage(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->scope->hasGlobalScope($user)
            && $user->hasPermission('platform_operators.manage');
    }
}
