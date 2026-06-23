<?php

namespace App\Policies;

use App\Models\CorrectionRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class CorrectionRequestPolicy
{
    use ChecksPermissions;

    private const MODULE = 'administrative_processes';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, CorrectionRequest $correctionRequest): bool
    {
        if ($user->hasRole('candidate')) {
            return $correctionRequest->user_id === $user->id
                && $correctionRequest->candidate_visible
                && $this->canAccess($user, self::MODULE, 'view');
        }

        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, CorrectionRequest $correctionRequest): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'update');
    }
}
