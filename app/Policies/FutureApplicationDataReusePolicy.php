<?php

namespace App\Policies;

use App\Models\FutureApplicationDataReuse;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;

class FutureApplicationDataReusePolicy
{
    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('applications', 'view');
    }

    public function view(User $user, FutureApplicationDataReuse $reuse): bool
    {
        return $reuse->user_id === $user->id || $user->hasPermissionTo('applications', 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate') || $user->hasPermissionTo('applications', 'create');
    }

    public function update(User $user, FutureApplicationDataReuse $reuse): bool
    {
        return $reuse->user_id === $user->id;
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->hasPermission('applications.view')
            && $user->municipality_id !== null;
    }

    public function viewBackoffice(User $user, FutureApplicationDataReuse $reuse): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsFutureApplicationDataReuse($user, $reuse);
    }
}
