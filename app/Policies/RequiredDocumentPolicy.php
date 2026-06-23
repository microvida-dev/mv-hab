<?php

namespace App\Policies;

use App\Models\RequiredDocument;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class RequiredDocumentPolicy
{
    use ChecksPermissions;

    private const MODULE = 'documents';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, RequiredDocument $requiredDocument): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, RequiredDocument $requiredDocument): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, RequiredDocument $requiredDocument): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }
}
