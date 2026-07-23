<?php

namespace App\Policies;

use App\Models\DataSubjectRequest;
use App\Models\User;
use App\Services\Rgpd\PrivacyMunicipalScopeService;

class DataSubjectRequestPolicy
{
    public function __construct(
        private readonly PrivacyMunicipalScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('privacy.view');
    }

    public function view(User $user, DataSubjectRequest $request): bool
    {
        return $request->user_id === $user->id
            || (
                $user->hasPermission('privacy.view')
                && $this->scope->ownsRequest($user, $request)
            );
    }

    public function create(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('privacy.create');
    }

    public function update(User $user, DataSubjectRequest $request): bool
    {
        return $user->hasPermission('privacy.update')
            && $this->scope->ownsRequest($user, $request);
    }

    public function assign(User $user, DataSubjectRequest $request): bool
    {
        return $user->hasPermission('privacy.assign')
            && $this->scope->ownsRequest($user, $request);
    }

    public function approve(User $user, DataSubjectRequest $request): bool
    {
        return $user->hasPermission('privacy.approve')
            && $this->scope->ownsRequest($user, $request);
    }

    public function reject(User $user, DataSubjectRequest $request): bool
    {
        return $user->hasPermission('privacy.reject')
            && $this->scope->ownsRequest($user, $request);
    }

    public function export(User $user, DataSubjectRequest $request): bool
    {
        return $user->hasPermission('privacy.export')
            && $this->scope->ownsRequest($user, $request);
    }
}
