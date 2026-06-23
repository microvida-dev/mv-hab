<?php

namespace App\Policies;

use App\Models\DocumentType;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class DocumentTypePolicy
{
    use ChecksPermissions;

    private const MODULE = 'documents';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, DocumentType $documentType): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, DocumentType $documentType): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, DocumentType $documentType): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }
}
