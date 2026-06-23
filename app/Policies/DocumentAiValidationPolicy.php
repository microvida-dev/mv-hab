<?php

namespace App\Policies;

use App\Models\DocumentAiValidation;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class DocumentAiValidationPolicy
{
    use ChecksPermissions;

    private const MODULE = 'documents';

    public function viewAny(User $user): bool
    {
        return app(DocumentAiValidationRunPolicy::class)->viewAny($user);
    }

    public function view(User $user, DocumentAiValidation $validation): bool
    {
        return $this->viewAny($user);
    }

    public function viewSensitive(User $user, DocumentAiValidation $validation): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'audit')
                || $user->hasPermission('audit_logs.view')
                || $user->hasPermission('*')
            );
    }

    public function viewHealth(User $user, DocumentAiValidation $validation): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'audit')
                || $user->hasPermission('audit_logs.view')
                || $user->hasPermission('privacy.view')
                || $user->hasPermission('*')
            );
    }

    public function markManualReview(User $user, DocumentAiValidation $validation): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'update')
                || $this->canAccess($user, self::MODULE, 'approve')
                || $this->canAccess($user, self::MODULE, 'audit')
            );
    }
}
