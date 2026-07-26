<?php

namespace App\Policies;

use App\Models\DocumentTemplate;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Platform\PlatformOperatorScopeService;

class DocumentTemplatePolicy
{
    use ChecksPermissions;

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly PlatformOperatorScopeService $platformScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->viewAnyBackoffice($user);
    }

    public function view(User $user, DocumentTemplate $template): bool
    {
        return $this->viewBackoffice($user, $template);
    }

    public function create(User $user): bool
    {
        return $this->createBackoffice($user);
    }

    public function update(User $user, DocumentTemplate $template): bool
    {
        return $this->updateBackoffice($user, $template);
    }

    public function approve(User $user, DocumentTemplate $template): bool
    {
        return $this->approveBackoffice($user, $template);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'documents', 'view');
    }

    public function viewBackoffice(User $user, DocumentTemplate $template): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsDocumentTemplate($user, $template);
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'create')
            && (
                $user->municipality_id !== null
                || $this->platformScope->hasGlobalScope($user)
            );
    }

    public function updateBackoffice(User $user, DocumentTemplate $template): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'update')
            && $this->municipalScope->canMutateDocumentTemplate($user, $template);
    }

    public function createVersionBackoffice(User $user, DocumentTemplate $template): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'create')
            && $this->municipalScope->canMutateDocumentTemplate($user, $template);
    }

    public function archiveBackoffice(User $user, DocumentTemplate $template): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'archive')
            && $this->municipalScope->canMutateDocumentTemplate($user, $template);
    }

    public function previewBackoffice(User $user, DocumentTemplate $template): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'documents', 'preview')
            && $this->municipalScope->ownsDocumentTemplate($user, $template);
    }

    public function approveBackoffice(User $user, DocumentTemplate $template): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'approve')
            && $this->municipalScope->canMutateDocumentTemplate($user, $template);
    }
}
