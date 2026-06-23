<?php

namespace App\Policies;

use App\Models\GeneratedOfficialDocument;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;

class GeneratedOfficialDocumentPolicy
{
    use ChecksCommunicationAccess;

    public function viewAny(User $user): bool
    {
        return $this->canViewCommunications($user);
    }

    public function view(User $user, GeneratedOfficialDocument $document): bool
    {
        return $user->hasRole('candidate')
            ? $document->recipient_user_id === $user->id
            : $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateCommunications($user);
    }

    public function update(User $user, GeneratedOfficialDocument $document): bool
    {
        return $this->canManageCommunications($user);
    }
}
