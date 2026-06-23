<?php

namespace App\Policies;

use App\Models\DocumentAiValidationRun;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class DocumentAiValidationRunPolicy
{
    use ChecksPermissions;

    private const MODULE = 'documents';

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'view')
                || $this->canAccess($user, self::MODULE, 'audit')
            );
    }

    public function view(User $user, DocumentAiValidationRun $run): bool
    {
        return $this->viewAny($user);
    }

    public function rerun(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'update')
                || $this->canAccess($user, self::MODULE, 'approve')
                || $this->canAccess($user, self::MODULE, 'audit')
            );
    }
}
