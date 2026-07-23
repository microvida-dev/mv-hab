<?php

namespace App\Policies;

use App\Models\DataExportPackage;
use App\Models\User;
use App\Services\Rgpd\PrivacyMunicipalScopeService;

class DataExportPackagePolicy
{
    public function __construct(
        private readonly PrivacyMunicipalScopeService $scope,
    ) {}

    public function view(User $user, DataExportPackage $package): bool
    {
        return $package->user_id === $user->id
            || (
                $user->hasPermission('privacy.export')
                && $this->scope->ownsExport($user, $package)
            );
    }

    public function download(User $user, DataExportPackage $package): bool
    {
        return $this->view($user, $package);
    }

    public function create(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('privacy.export');
    }
}
