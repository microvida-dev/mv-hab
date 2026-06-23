<?php

namespace App\Policies;

use App\Models\EncryptedFieldRegistry;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class EncryptedFieldRegistryPolicy
{
    use HandlesSecurityAccess;

    public function viewAny(User $user): bool
    {
        return $this->backoffice($user, 'audit');
    }

    public function view(User $user, EncryptedFieldRegistry $registry): bool
    {
        return $this->backoffice($user, 'audit');
    }
}
