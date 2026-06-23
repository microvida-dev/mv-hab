<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class DocumentPolicy
{
    use ChecksPermissions;

    private const MODULE = 'documents';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, Document $document): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, Document $document): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }

    public function approve(User $user, Document $document): bool
    {
        return $this->canAccess($user, self::MODULE, 'approve');
    }

    public function reject(User $user, Document $document): bool
    {
        return $this->canAccess($user, self::MODULE, 'reject');
    }
}
