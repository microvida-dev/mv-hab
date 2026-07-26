<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\PropertyInspection;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class PropertyInspectionPolicy
{
    use ChecksMaintenanceAccess;

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /*
     * Compatibilidade com o portal tenant.
     * O backoffice deve utilizar as abilities *Backoffice.
     */

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(
            'inspections',
            'view',
        );
    }

    public function view(
        User $user,
        PropertyInspection $inspection,
    ): bool {
        if ($user->hasRole('candidate')) {
            return $inspection->tenant_visible
                && $this->ownsContract(
                    $user,
                    $this->contract($inspection),
                );
        }

        return $user->hasPermissionTo(
            'inspections',
            'view',
        );
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $user->hasPermissionTo(
                'inspections',
                'create',
            );
    }

    public function update(
        User $user,
        PropertyInspection $inspection,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $user->hasPermissionTo(
                'inspections',
                'update',
            );
    }

    public function approve(
        User $user,
        PropertyInspection $inspection,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $user->hasPermissionTo(
                'inspections',
                'approve',
            );
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission(
            'inspections.view',
        ) && $this->municipalScope
            ->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(
        User $user,
        PropertyInspection $inspection,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope
                ->ownsPropertyInspection($user, $inspection);
    }

    public function createBackoffice(User $user): bool
    {
        return $user->hasPermission(
            'inspections.create',
        ) && $this->municipalScope
            ->hasMunicipalOrGlobalScope($user);
    }

    public function updateBackoffice(
        User $user,
        PropertyInspection $inspection,
    ): bool {
        return $user->hasPermission(
            'inspections.update',
        ) && $this->municipalScope
            ->ownsPropertyInspection($user, $inspection);
    }

    public function startBackoffice(
        User $user,
        PropertyInspection $inspection,
    ): bool {
        return $user->hasPermission(
            'inspections.start',
        ) && $this->municipalScope
            ->ownsPropertyInspection($user, $inspection);
    }

    public function completeBackoffice(
        User $user,
        PropertyInspection $inspection,
    ): bool {
        return $user->hasPermission(
            'inspections.complete',
        ) && $this->municipalScope
            ->ownsPropertyInspection($user, $inspection);
    }

    public function validateBackoffice(
        User $user,
        PropertyInspection $inspection,
    ): bool {
        return $user->hasPermission(
            'inspections.validate',
        ) && $this->municipalScope
            ->ownsPropertyInspection($user, $inspection);
    }

    public function closeBackoffice(
        User $user,
        PropertyInspection $inspection,
    ): bool {
        return $user->hasPermission(
            'inspections.close',
        ) && $this->municipalScope
            ->ownsPropertyInspection($user, $inspection);
    }

    public function cancelBackoffice(
        User $user,
        PropertyInspection $inspection,
    ): bool {
        return $user->hasPermission(
            'inspections.cancel',
        ) && $this->municipalScope
            ->ownsPropertyInspection($user, $inspection);
    }

    private function contract(
        PropertyInspection $inspection,
    ): ?Contract {
        $contract = $inspection->leaseContract;

        return $contract instanceof Contract
            ? $contract
            : null;
    }
}
