<?php

namespace App\Policies;

use App\Models\AuditEvent;
use App\Models\User;
use App\Services\Security\SecurityMunicipalScopeService;

class AuditEventPolicy
{
    public function __construct(
        private readonly SecurityMunicipalScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('audit_logs.view');
    }

    public function view(User $user, AuditEvent $event): bool
    {
        return $this->viewAny($user)
            && $this->scope->ownsAuditEvent($user, $event);
    }

    public function export(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('audit_logs.export');
    }

    public function update(User $user, AuditEvent $event): bool
    {
        return false;
    }

    public function delete(User $user, AuditEvent $event): bool
    {
        return false;
    }
}
