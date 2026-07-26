<?php

namespace App\Policies;

use App\Models\GeneratedProcedureDocument;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class GeneratedProcedureDocumentPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->viewAnyBackoffice($user);
    }

    public function view(User $user, GeneratedProcedureDocument $document): bool
    {
        return $this->viewBackoffice($user, $document);
    }

    public function download(User $user, GeneratedProcedureDocument $document): bool
    {
        return $this->downloadBackoffice($user, $document);
    }

    public function approve(User $user, GeneratedProcedureDocument $document): bool
    {
        return $this->issueBackoffice($user, $document);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'documents', 'view');
    }

    public function viewBackoffice(User $user, GeneratedProcedureDocument $document): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsGeneratedProcedureDocument($user, $document);
    }

    public function downloadBackoffice(User $user, GeneratedProcedureDocument $document): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'documents', 'download')
            && $this->municipalScope->ownsGeneratedProcedureDocument($user, $document);
    }

    public function issueBackoffice(User $user, GeneratedProcedureDocument $document): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'issue')
            && $this->municipalScope->ownsGeneratedProcedureDocument($user, $document);
    }
}
