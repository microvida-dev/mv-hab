<?php

namespace App\Policies;

use App\Models\SimulatorConfiguration;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class SimulatorConfigurationPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function view(User $user, SimulatorConfiguration $simulatorConfiguration): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'simulator', 'view');
    }

    public function update(User $user, SimulatorConfiguration $simulatorConfiguration): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'simulator', 'update');
    }

    public function viewBackoffice(User $user, SimulatorConfiguration $configuration): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'simulator', 'view')
            && $this->municipalScope->ownsSimulatorConfiguration($user, $configuration);
    }

    public function updateBackoffice(User $user, SimulatorConfiguration $configuration): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'simulator', 'update')
            && $this->municipalScope->ownsSimulatorConfiguration($user, $configuration);
    }
}
