<?php

namespace App\Policies;

use App\Models\TemplateVariable;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;

class TemplateVariablePolicy
{
    use ChecksCommunicationAccess;

    public function viewAny(User $user): bool
    {
        return $this->canViewCommunications($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageCommunications($user);
    }

    public function update(User $user, TemplateVariable $variable): bool
    {
        return $this->canManageCommunications($user);
    }
}
