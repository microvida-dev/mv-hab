<?php

namespace App\Policies;

use App\Models\InternalAlert;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;

class InternalAlertPolicy
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && ($user->hasPermission('reports.view') || $user->hasPermission('applications.view'));
    }

    public function view(User $user, InternalAlert $alert): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, InternalAlert $alert): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && ($user->hasPermission('reports.update') || $user->hasPermission('applications.update'));
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission('internal_alerts.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, InternalAlert $alert): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsInternalAlert($user, $alert);
    }

    public function detectBackoffice(User $user): bool
    {
        return $user->hasPermission('internal_alerts.detect')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function resolveBackoffice(User $user, InternalAlert $alert): bool
    {
        return $user->hasPermission('internal_alerts.resolve')
            && $this->municipalScope->ownsInternalAlert($user, $alert);
    }

    public function dismissBackoffice(User $user, InternalAlert $alert): bool
    {
        return $user->hasPermission('internal_alerts.dismiss')
            && $this->municipalScope->ownsInternalAlert($user, $alert);
    }
}
