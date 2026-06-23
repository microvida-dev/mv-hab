<?php

namespace App\Policies;

use App\Enums\DocumentStatus;
use App\Models\DocumentSubmission;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class DocumentSubmissionPolicy
{
    use ChecksPermissions;

    private const MODULE = 'documents';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, DocumentSubmission $documentSubmission): bool
    {
        if ($user->hasRole('candidate')) {
            return $this->owns($user, $documentSubmission);
        }

        return $this->canAccess($user, self::MODULE, 'view');
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
            && $this->canAccess($user, self::MODULE, 'approve');
    }

    public function reject(User $user, DocumentSubmission $documentSubmission): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'reject');
    }

    private function owns(User $user, DocumentSubmission $documentSubmission): bool
    {
        return $user->hasRole('candidate')
            && $documentSubmission->adhesionRegistration?->user_id === $user->id;
    }
}
