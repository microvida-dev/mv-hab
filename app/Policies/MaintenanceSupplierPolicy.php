<?php

namespace App\Policies;

use App\Models\MaintenanceSupplier;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;

class MaintenanceSupplierPolicy
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->viewAnyBackoffice($user);
    }

    public function view(
        User $user,
        MaintenanceSupplier $supplier,
    ): bool {
        return $this->viewBackoffice($user, $supplier);
    }

    public function create(User $user): bool
    {
        return $this->createBackoffice($user);
    }

    public function update(
        User $user,
        MaintenanceSupplier $supplier,
    ): bool {
        return $this->updateBackoffice($user, $supplier);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission(
            'maintenance.suppliers.view',
        ) && $this->municipalScope
            ->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(
        User $user,
        MaintenanceSupplier $supplier,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope
                ->ownsMaintenanceSupplier($user, $supplier);
    }

    public function createBackoffice(User $user): bool
    {
        return $user->hasPermission(
            'maintenance.suppliers.create',
        ) && $user->municipality_id !== null;
    }

    public function updateBackoffice(
        User $user,
        MaintenanceSupplier $supplier,
    ): bool {
        return $user->hasPermission(
            'maintenance.suppliers.update',
        ) && $this->municipalScope
            ->canMutateMaintenanceSupplier($user, $supplier);
    }
}
