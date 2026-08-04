<?php

namespace App\Services\Municipalities;

use App\Models\PlatformOperatorAssignment;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\AccessChangeLogger;
use App\Services\Platform\PlatformOperatorScopeService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;

final class PlatformMunicipalRoleAssignmentService
{
    public function __construct(
        private readonly PlatformOperatorScopeService $platformScope,
        private readonly AccessChangeLogger $logger,
    ) {}

    public function assignInitialAdministrator(
        User $actor,
        User $target,
        Role $role,
        string $justification,
    ): void {
        if (! $this->platformScope->hasGlobalScope($actor)
            || ! $actor->hasPermission('municipalities.create')
            || $actor->municipality_id !== null) {
            throw new AuthorizationException(
                'A atribuição inicial exige um operador global autorizado.',
            );
        }

        if ($target->status !== 'active'
            || $target->municipality_id === null
            || $target->hasRole(['candidate', 'auditor'])) {
            throw new DomainException('O administrador municipal alvo não é elegível.');
        }

        if (! $role->isActive()
            || ! $role->isMunicipalCustom()
            || $role->template_key !== MunicipalityOnboardingPlanner::TEMPLATE_KEY
            || (int) $role->municipality_id !== (int) $target->municipality_id) {
            throw new DomainException('A role indicada não é a role administrativa do Município alvo.');
        }

        if ($role->permissions()->where('name', 'like', '%*%')->exists()) {
            throw new DomainException('A role administrativa municipal não pode conter wildcards.');
        }

        if (PlatformOperatorAssignment::query()->where('user_id', $target->id)->exists()) {
            throw new DomainException('O administrador municipal não pode possuir assignment global.');
        }

        $lockedTarget = User::query()->whereKey($target->id)->lockForUpdate()->firstOrFail();
        $lockedRole = Role::query()->whereKey($role->id)->lockForUpdate()->firstOrFail();

        if ($lockedTarget->hasRole($lockedRole->name)) {
            return;
        }

        $before = $lockedTarget->roles()->pluck('roles.id')->sort()->values()->all();
        $lockedTarget->roles()->syncWithoutDetaching([$lockedRole->id]);
        $after = $lockedTarget->roles()->pluck('roles.id')->sort()->values()->all();

        $this->logger->record(
            'municipal_administrator_assigned',
            $actor,
            $justification,
            target: $lockedTarget,
            role: $lockedRole,
            oldValues: ['role_ids' => $before],
            newValues: ['role_ids' => $after],
        );
    }
}
