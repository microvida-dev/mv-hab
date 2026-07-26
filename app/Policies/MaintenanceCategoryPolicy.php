<?php

namespace App\Policies;

use App\Models\MaintenanceCategory;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;

class MaintenanceCategoryPolicy
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
        MaintenanceCategory $category,
    ): bool {
        return $this->viewBackoffice($user, $category);
    }

    public function create(User $user): bool
    {
        return $this->createBackoffice($user);
    }

    public function update(
        User $user,
        MaintenanceCategory $category,
    ): bool {
        return $this->updateBackoffice($user, $category);
    }

    public function delete(
        User $user,
        MaintenanceCategory $category,
    ): bool {
        return $this->deleteBackoffice($user, $category);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission(
            'maintenance.categories.view',
        ) && $this->municipalScope
            ->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(
        User $user,
        MaintenanceCategory $category,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope
                ->ownsMaintenanceCategory($user, $category);
    }

    public function createBackoffice(User $user): bool
    {
        return $user->hasPermission(
            'maintenance.categories.create',
        ) && $user->municipality_id !== null;
    }

    public function updateBackoffice(
        User $user,
        MaintenanceCategory $category,
    ): bool {
        return $user->hasPermission(
            'maintenance.categories.update',
        ) && $this->municipalScope
            ->canMutateMaintenanceCategory($user, $category);
    }

    public function deleteBackoffice(
        User $user,
        MaintenanceCategory $category,
    ): bool {
        return $user->hasPermission(
            'maintenance.categories.delete',
        ) && $this->municipalScope
            ->canMutateMaintenanceCategory($user, $category);
    }
}
