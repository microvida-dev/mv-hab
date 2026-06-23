<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class AuditLogPolicy
{
    use ChecksPermissions;

    private const MODULE = 'audit_logs';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function export(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'export');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
