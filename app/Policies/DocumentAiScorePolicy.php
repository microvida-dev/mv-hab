<?php

namespace App\Policies;

use App\Models\DocumentAiScore;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class DocumentAiScorePolicy
{
    use ChecksPermissions;

    private const MODULE = 'documents';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'view')
                || $this->canAccess($user, self::MODULE, 'audit')
            );
    }

    public function view(User $user, DocumentAiScore $score): bool
    {
        return $this->viewAny($user);
    }

    public function recalculate(User $user, DocumentAiScore $score): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'update')
                || $this->canAccess($user, self::MODULE, 'approve')
                || $this->canAccess($user, self::MODULE, 'audit')
            );
    }

    public function viewBackoffice(User $user, DocumentAiScore $score): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $this->municipalScope->ownsDocumentAiScore($user, $score);
    }
}
