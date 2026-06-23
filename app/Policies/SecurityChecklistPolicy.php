<?php

namespace App\Policies;

use App\Models\SecurityChecklist;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class SecurityChecklistPolicy
{
    use HandlesSecurityAccess;

    public function viewAny(User $user): bool
    {
        return $this->backoffice($user, 'audit');
    }

    public function view(User $user, SecurityChecklist $checklist): bool
    {
        return $this->backoffice($user, 'audit');
    }

    public function create(User $user): bool
    {
        return $this->backoffice($user, 'audit');
    }

    public function update(User $user, SecurityChecklist $checklist): bool
    {
        return $this->backoffice($user, 'audit');
    }
}
