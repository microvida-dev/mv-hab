<?php

namespace App\Policies;

use App\Models\DocumentTemplateVersion;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class DocumentTemplateVersionPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function view(User $user, DocumentTemplateVersion $version): bool
    {
        return $this->viewBackoffice($user, $version);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'create');
    }

    public function approve(User $user, DocumentTemplateVersion $version): bool
    {
        return $this->approveBackoffice($user, $version);
    }

    public function viewBackoffice(User $user, DocumentTemplateVersion $version): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'documents', 'view')
            && $this->municipalScope->ownsDocumentTemplateVersion($user, $version);
    }

    public function approveBackoffice(User $user, DocumentTemplateVersion $version): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'approve')
            && $this->municipalScope->canMutateDocumentTemplateVersion($user, $version);
    }

    public function activateBackoffice(User $user, DocumentTemplateVersion $version): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'activate')
            && $this->municipalScope->canMutateDocumentTemplateVersion($user, $version);
    }
}
