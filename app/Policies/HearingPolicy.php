<?php

namespace App\Policies;

use App\Models\Hearing;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class HearingPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'complaints', 'view');
    }

    public function view(User $user, Hearing $hearing): bool
    {
        return $user->hasRole('candidate')
            ? $hearing->user_id === $user->id && $hearing->candidate_visible && $this->canAccess($user, 'complaints', 'view')
            : $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'complaints', 'create');
    }

    public function update(User $user, Hearing $hearing): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'complaints', 'update');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->municipality_id !== null
            && $this->canAccess($user, 'hearings', 'view');
    }

    public function viewBackoffice(User $user, Hearing $hearing): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsHearing($user, $hearing);
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $user->municipality_id !== null
            && $this->canAccess($user, 'hearings', 'create');
    }

    public function issueBackoffice(User $user, Hearing $hearing): bool
    {
        return $this->canMutateBackoffice($user, $hearing, 'issue');
    }

    public function closeBackoffice(User $user, Hearing $hearing): bool
    {
        return $this->canMutateBackoffice($user, $hearing, 'close');
    }

    public function cancelBackoffice(User $user, Hearing $hearing): bool
    {
        return $this->canMutateBackoffice($user, $hearing, 'cancel');
    }

    private function canMutateBackoffice(User $user, Hearing $hearing, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'hearings', $action)
            && $this->municipalScope->ownsHearing($user, $hearing);
    }
}
