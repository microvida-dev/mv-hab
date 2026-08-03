<?php

namespace App\Policies;

use App\Models\ProcedureMinute;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ProcedureMinutePolicy
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermissionTo('documents', 'view');
    }

    public function view(User $user, ProcedureMinute $minute): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->hasPermission('documents.generate')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function approve(User $user, ProcedureMinute $minute): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('documents', 'approve');
    }

    public function download(User $user, ProcedureMinute $minute): bool
    {
        return $this->view($user, $minute);
    }

    public function delete(User $user, ProcedureMinute $minute): bool
    {
        return $minute->approved_at === null
            && ! $user->hasRole(['candidate', 'auditor'])
            && $user->hasPermissionTo('documents', 'delete');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->hasPermission('documents.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(
        User $user,
        ProcedureMinute $minute,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsProcedureMinute($user, $minute);
    }

    public function approveBackoffice(
        User $user,
        ProcedureMinute $minute,
    ): bool {
        return $user->hasPermission('documents.approve')
            && $this->municipalScope->ownsProcedureMinute($user, $minute);
    }

    public function downloadBackoffice(
        User $user,
        ProcedureMinute $minute,
    ): bool {
        return $user->hasPermission('documents.download')
            && $this->municipalScope->ownsProcedureMinute($user, $minute);
    }

    public function deleteBackoffice(
        User $user,
        ProcedureMinute $minute,
    ): bool {
        return $minute->approved_at === null
            && $user->hasPermission('documents.delete')
            && $this->municipalScope->ownsProcedureMinute($user, $minute);
    }
}
