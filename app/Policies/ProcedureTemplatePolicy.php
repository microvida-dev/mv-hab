<?php

namespace App\Policies;

use App\Models\ProcedureTemplate;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Platform\PlatformOperatorScopeService;

class ProcedureTemplatePolicy
{
    use ChecksPermissions;

    public function __construct(
        private readonly PlatformOperatorScopeService $platformScope,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

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

    public function viewBackoffice(User $user, ProcedureTemplate $template): bool
    {
        return $this->viewAnyBackoffice($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'documents', 'view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function previewBackoffice(
        User $user,
        ProcedureTemplate $template,
    ): bool {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'documents', 'preview')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function generateBackoffice(User $user, ProcedureTemplate $template): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'documents', 'generate')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function createBackoffice(User $user): bool
    {
        return $this->canAccess($user, 'documents', 'create')
            && $this->platformScope->hasGlobalScope($user);
    }

    public function updateBackoffice(User $user, ProcedureTemplate $template): bool
    {
        return $this->canAccess($user, 'documents', 'update')
            && $this->platformScope->hasGlobalScope($user);
    }

    public function publishBackoffice(User $user, ProcedureTemplate $template): bool
    {
        return $this->canAccess($user, 'documents', 'publish')
            && $this->platformScope->hasGlobalScope($user);
    }
}
