<?php

namespace App\Policies;

use App\Models\GeneratedOfficialDocument;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class GeneratedOfficialDocumentPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->viewAnyBackoffice($user);
    }

    public function view(User $user, GeneratedOfficialDocument $document): bool
    {
        if ($user->hasRole('candidate')) {
            return $document->recipient_user_id === $user->id;
        }

        return $this->viewBackoffice($user, $document);
    }

    public function create(User $user): bool
    {
        return $this->createBackoffice($user);
    }

    public function update(User $user, GeneratedOfficialDocument $document): bool
    {
        return $this->issueBackoffice($user, $document);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'documents', 'view');
    }

    public function viewBackoffice(User $user, GeneratedOfficialDocument $document): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsGeneratedOfficialDocument($user, $document);
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'generate');
    }

    public function downloadBackoffice(User $user, GeneratedOfficialDocument $document): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'documents', 'download')
            && $this->municipalScope->ownsGeneratedOfficialDocument($user, $document);
    }

    public function issueBackoffice(User $user, GeneratedOfficialDocument $document): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'issue')
            && $this->municipalScope->ownsGeneratedOfficialDocument($user, $document);
    }

    public function cancelBackoffice(User $user, GeneratedOfficialDocument $document): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'cancel')
            && $this->municipalScope->ownsGeneratedOfficialDocument($user, $document);
    }
}
