<?php

namespace App\Policies;

use App\Models\DataExportPackage;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class DataExportPackagePolicy
{
    use HandlesSecurityAccess;

    public function view(User $user, DataExportPackage $package): bool
    {
        return $package->user_id === $user->id || $this->privacy($user);
    }

    public function download(User $user, DataExportPackage $package): bool
    {
        return $this->view($user, $package);
    }

    public function create(User $user): bool
    {
        return $this->privacy($user, 'export');
    }
}
