<?php

namespace App\Policies;

use App\Models\AccessChangeEvent;
use App\Models\User;
use App\Services\Access\AccessMunicipalScopeService;

class AccessAuditPolicy
{
    public function __construct(private readonly AccessMunicipalScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view') && $user->municipality_id !== null;
    }

    public function view(User $user, AccessChangeEvent $event): bool
    {
        return $this->can($user, 'view')
            && $this->municipalScope->accessEvents(
                AccessChangeEvent::query()->whereKey($event),
                $user,
            )->exists();
    }

    private function can(User $user, string $action): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission("access_audit.{$action}");
    }
}
