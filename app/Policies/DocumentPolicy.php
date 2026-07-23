<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class DocumentPolicy
{
    use ChecksPermissions;

    private const MODULE = 'documents';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

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

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $user->municipality_id !== null;
    }

    public function viewBackoffice(User $user, Document $document): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsDocument($user, $document);
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'create')
            && $user->municipality_id !== null;
    }

    public function updateBackoffice(User $user, Document $document): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update')
            && $this->municipalScope->ownsDocument($user, $document);
    }

    public function deleteBackoffice(User $user, Document $document): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'delete')
            && $this->municipalScope->ownsDocument($user, $document);
    }
}
