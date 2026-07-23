<?php

namespace App\Services\Security;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\PermissionCatalogService;

class MfaEnforcementService
{
    private const SENSITIVE_ROLES = [
        'administrator',
        'municipal_technician',
        'jury',
        'legal_manager',
        'financial_manager',
        'housing_manager',
        'inspection_manager',
        'maintenance_manager',
        'auditor',
    ];

    public function __construct(private readonly PermissionCatalogService $permissions) {}

    public function requiresMfa(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->mfa_required) {
            return true;
        }

        return $user->roles()
            ->active()
            ->with('permissions:id,name,module,action')
            ->get()
            ->contains(fn (Role $role): bool => $this->roleRequiresMfa($role));
    }

    public function roleRequiresMfa(Role $role): bool
    {
        if (! $role->isActive()) {
            return false;
        }

        if ($this->isLegacySensitiveRole($role->name)) {
            return true;
        }

        $role->loadMissing('permissions:id,name,module,action');

        return $role->permissions->contains(
            fn (Permission $permission): bool => $this->permissions->isSensitive(
                $permission->name,
                $permission->module,
                $permission->action,
            ),
        );
    }

    public function isLegacySensitiveRole(string $roleName): bool
    {
        return in_array($roleName, self::SENSITIVE_ROLES, true);
    }

    public function hasConfirmedDevice(User $user): bool
    {
        return $user->mfaDevices()
            ->whereNotNull('confirmed_at')
            ->whereNull('disabled_at')
            ->exists();
    }

    public function sessionVerified(): bool
    {
        return (bool) session('mfa.verified_at')
            && now()->diffInMinutes(session('mfa.verified_at')) <= 480;
    }

    public function markVerified(): void
    {
        session(['mfa.verified_at' => now()]);
    }

    public function forgetVerification(): void
    {
        session()->forget('mfa.verified_at');
    }
}
