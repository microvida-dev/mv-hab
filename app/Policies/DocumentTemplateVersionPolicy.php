<?php

namespace App\Policies;

use App\Models\DocumentTemplateVersion;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;

class DocumentTemplateVersionPolicy
{
    use ChecksCommunicationAccess;

    public function view(User $user, DocumentTemplateVersion $version): bool
    {
        return $this->canViewCommunications($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageCommunications($user);
    }

    public function approve(User $user, DocumentTemplateVersion $version): bool
    {
        return $this->canPublishCommunications($user);
    }
}
