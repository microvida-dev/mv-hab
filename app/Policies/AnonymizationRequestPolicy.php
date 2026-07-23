<?php

namespace App\Policies;

use App\Models\AnonymizationRequest;
use App\Models\User;
use App\Services\Rgpd\PrivacyMunicipalScopeService;

class AnonymizationRequestPolicy
{
    public function __construct(
        private readonly PrivacyMunicipalScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('rgpd.anonymization.view');
    }

    public function view(User $user, AnonymizationRequest $request): bool
    {
        return $this->viewAny($user)
            && $this->scope->ownsAnonymizationRequest($user, $request);
    }

    public function create(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('rgpd.anonymization.request');
    }

    public function approve(User $user, AnonymizationRequest $request): bool
    {
        return $user->hasPermission('rgpd.anonymization.approve')
            && $this->scope->ownsAnonymizationRequest($user, $request);
    }

    public function execute(User $user, AnonymizationRequest $request): bool
    {
        return $user->hasPermission('rgpd.anonymization.execute')
            && $this->scope->ownsAnonymizationRequest($user, $request);
    }
}
