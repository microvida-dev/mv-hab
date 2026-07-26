<?php

namespace App\Policies;

use App\Models\ConsentPurpose;
use App\Models\User;
use App\Services\Rgpd\PrivacyMunicipalScopeService;

class ConsentPurposePolicy
{
    public function __construct(
        private readonly PrivacyMunicipalScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('privacy.view');
    }

    public function view(User $user, ConsentPurpose $purpose): bool
    {
        return $this->viewAny($user)
            && $this->scope
                ->purposes(ConsentPurpose::query()->whereKey($purpose), $user)
                ->exists();
    }

    public function create(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('privacy.create');
    }

    public function update(User $user, ConsentPurpose $purpose): bool
    {
        return $user->hasPermission('privacy.update')
            && $this->scope->ownsMutablePurpose($user, $purpose);
    }
}
