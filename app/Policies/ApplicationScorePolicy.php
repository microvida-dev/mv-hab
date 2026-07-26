<?php

namespace App\Policies;

use App\Models\ApplicationScore;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ApplicationScorePolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'scoring', 'view');
    }

    public function view(User $user, ApplicationScore $score): bool
    {
        return $this->viewAny($user);
    }

    public function manualReview(User $user, ApplicationScore $score): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && ($this->canAccess($user, 'scoring', 'update') || $this->canAccess($user, 'scoring', 'approve'));
    }

    public function lock(User $user, ApplicationScore $score): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'approve');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->municipality_id !== null
            && $this->canAccess($user, 'scoring', 'view');
    }

    public function viewBackoffice(User $user, ApplicationScore $score): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsApplicationScore($user, $score);
    }

    public function reviewBackoffice(User $user, ApplicationScore $score): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'review')
            && $this->municipalScope->ownsApplicationScore($user, $score);
    }

    public function lockBackoffice(User $user, ApplicationScore $score): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'lock')
            && $this->municipalScope->ownsApplicationScore($user, $score);
    }
}
