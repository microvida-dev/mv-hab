<?php

namespace App\Policies;

use App\Enums\DocumentStatus;
use App\Models\DocumentSubmission;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class DocumentSubmissionPolicy
{
    use ChecksPermissions;

    private const MODULE = 'documents';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, DocumentSubmission $documentSubmission): bool
    {
        if ($user->hasRole('candidate')) {
            return $this->owns($user, $documentSubmission);
        }

        return $this->viewBackoffice($user, $documentSubmission);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate')
            && $user->hasPermissionTo(self::MODULE, 'create');
    }

    public function replace(User $user, DocumentSubmission $documentSubmission): bool
    {
        return $this->owns($user, $documentSubmission)
            && $user->hasPermissionTo(self::MODULE, 'update')
            && $documentSubmission->isReplaceable();
    }

    public function download(User $user, DocumentSubmission $documentSubmission): bool
    {
        return $this->view($user, $documentSubmission);
    }

    public function delete(User $user, DocumentSubmission $documentSubmission): bool
    {
        return $this->owns($user, $documentSubmission)
            && $user->hasPermissionTo(self::MODULE, 'update')
            && in_array($documentSubmission->status, [
                DocumentStatus::Submitted,
                DocumentStatus::Rejected,
                DocumentStatus::Expired,
            ], true);
    }

    public function review(User $user, DocumentSubmission $documentSubmission): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'approve')
            && $this->municipalScope->ownsDocumentSubmission($user, $documentSubmission);
    }

    public function reject(User $user, DocumentSubmission $documentSubmission): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'reject')
            && $this->municipalScope->ownsDocumentSubmission($user, $documentSubmission);
    }

    private function owns(User $user, DocumentSubmission $documentSubmission): bool
    {
        return $user->hasRole('candidate')
            && $documentSubmission->adhesionRegistration?->user_id === $user->id;
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view');
    }

    public function viewBackoffice(
        User $user,
        DocumentSubmission $documentSubmission,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsDocumentSubmission($user, $documentSubmission);
    }

    public function downloadBackoffice(
        User $user,
        DocumentSubmission $documentSubmission,
    ): bool {
        return $this->viewBackoffice($user, $documentSubmission);
    }

    public function reviewBackoffice(
        User $user,
        DocumentSubmission $documentSubmission,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'approve')
            && $this->municipalScope->ownsDocumentSubmission($user, $documentSubmission);
    }

    public function rejectBackoffice(
        User $user,
        DocumentSubmission $documentSubmission,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'reject')
            && $this->municipalScope->ownsDocumentSubmission($user, $documentSubmission);
    }
}
