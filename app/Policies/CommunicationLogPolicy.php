<?php

namespace App\Policies;

use App\Models\CommunicationLog;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class CommunicationLogPolicy
{
    use ChecksCommunicationAccess;

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->canViewCommunications($user);
    }

    public function view(User $user, CommunicationLog $communication): bool
    {
        return $user->hasRole('candidate')
            ? $communication->recipient_user_id === $user->id && $this->canViewCommunications($user)
            : $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateCommunications($user);
    }

    public function update(User $user, CommunicationLog $communication): bool
    {
        return $this->canManageCommunications($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission('communications.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(
        User $user,
        CommunicationLog $communication,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsCommunicationLog(
                $user,
                $communication,
            );
    }

    public function createBackoffice(User $user): bool
    {
        return $user->hasPermission('communications.create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function cancelBackoffice(
        User $user,
        CommunicationLog $communication,
    ): bool {
        return $user->hasPermission('communications.cancel')
            && $this->municipalScope->ownsCommunicationLog(
                $user,
                $communication,
            );
    }

    public function archiveBackoffice(
        User $user,
        CommunicationLog $communication,
    ): bool {
        return $user->hasPermission('communications.archive')
            && $this->municipalScope->ownsCommunicationLog(
                $user,
                $communication,
            );
    }
}
