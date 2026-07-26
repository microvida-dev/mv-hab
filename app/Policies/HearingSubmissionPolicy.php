<?php

namespace App\Policies;

use App\Models\HearingSubmission;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class HearingSubmissionPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'complaints', 'view');
    }

    public function view(User $user, HearingSubmission $submission): bool
    {
        return $user->hasRole('candidate')
            ? $submission->user_id === $user->id
            : $this->canAccess($user, 'complaints', 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate') && $this->canAccess($user, 'complaints', 'create');
    }

    public function review(User $user, HearingSubmission $submission): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'complaints', 'update');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->municipality_id !== null
            && $this->canAccess($user, 'hearings', 'view');
    }

    public function viewBackoffice(User $user, HearingSubmission $submission): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsHearingSubmission($user, $submission);
    }

    public function reviewBackoffice(User $user, HearingSubmission $submission): bool
    {
        return $this->canReviewBackoffice($user, $submission, 'review');
    }

    public function acceptBackoffice(User $user, HearingSubmission $submission): bool
    {
        return $this->canReviewBackoffice($user, $submission, 'accept');
    }

    public function rejectBackoffice(User $user, HearingSubmission $submission): bool
    {
        return $this->canReviewBackoffice($user, $submission, 'reject');
    }

    private function canReviewBackoffice(
        User $user,
        HearingSubmission $submission,
        string $action,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'hearings', $action)
            && $this->municipalScope->ownsHearingSubmission($user, $submission);
    }
}
