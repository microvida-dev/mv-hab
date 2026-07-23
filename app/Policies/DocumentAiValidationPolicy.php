<?php

namespace App\Policies;

use App\Models\DocumentAiValidation;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class DocumentAiValidationPolicy
{
    use ChecksPermissions;

    private const MODULE = 'documents';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

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
                $user->hasPermission('audit_logs.view')
                || $user->hasPermission('*')
            );
    }

    public function viewHealth(User $user, DocumentAiValidation $validation): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $user->hasPermission('audit_logs.view')
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

    public function viewAnyBackoffice(User $user): bool
    {
        return app(DocumentAiValidationRunPolicy::class)->viewAnyBackoffice($user);
    }

    public function viewBackoffice(User $user, DocumentAiValidation $validation): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsDocumentAiValidation($user, $validation);
    }

    public function reviewBackoffice(User $user, DocumentAiValidation $validation): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'review_ai')
            && $this->municipalScope->ownsDocumentAiValidation($user, $validation);
    }
}
