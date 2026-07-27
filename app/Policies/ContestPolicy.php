<?php

namespace App\Policies;

use App\Models\Contest;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ContestPolicy
{
    use ChecksPermissions;

    private const MODULE = 'contests';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, Contest $contest): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, Contest $contest): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, Contest $contest): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }

    public function publish(User $user, Contest $contest): bool
    {
        return $this->canAccess($user, self::MODULE, 'publish');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, Contest $contest): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $this->municipalScope->ownsContest($user, $contest);
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function updateBackoffice(User $user, Contest $contest): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update')
            && $this->municipalScope->ownsContest($user, $contest);
    }

    public function deleteBackoffice(User $user, Contest $contest): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'delete')
            && $this->municipalScope->ownsContest($user, $contest);
    }

    public function publishBackoffice(User $user, Contest $contest): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'publish')
            && $this->municipalScope->ownsContest($user, $contest);
    }

    public function closeBackoffice(User $user, Contest $contest): bool
    {
        return $this->canAccess($user, self::MODULE, 'close')
            && $this->municipalScope->ownsContest($user, $contest);
    }

    public function viewListsBackoffice(User $user, Contest $contest): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'public_lists', 'view')
            && $this->municipalScope->ownsContest($user, $contest);
    }

    public function generateBackoffice(User $user, Contest $contest): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'public_lists', 'generate')
            && $this->municipalScope->ownsContest($user, $contest);
    }
}
