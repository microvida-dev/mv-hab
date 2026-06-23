<?php

namespace App\Policies;

use App\Models\DocumentAiAnalysis;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class DocumentAiAssistantPolicy
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

    public function view(User $user, DocumentAiAnalysis $analysis): bool
    {
        return $this->viewAny($user);
    }

    public function recalculate(User $user, DocumentAiAnalysis $analysis): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'update')
                || $this->canAccess($user, self::MODULE, 'approve')
                || $this->canAccess($user, self::MODULE, 'audit')
            );
    }
}
