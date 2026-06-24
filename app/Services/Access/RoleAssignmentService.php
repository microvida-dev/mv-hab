<?php

namespace App\Services\Access;

use App\Models\Role;
use App\Models\User;
use App\Policies\RoleAssignmentPolicy;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class RoleAssignmentService
{
    public function __construct(
        private readonly AccessChangeLogger $logger,
        private readonly RoleAssignmentPolicy $policy,
    ) {}

    public function assign(User $actor, User $target, Role $role, string $justification): void
    {
        $this->authorizeAssign($actor, $target, $role);

        DB::transaction(function () use ($actor, $target, $role, $justification): void {
            $target->refresh();

            if ($target->hasRole($role->name)) {
                return;
            }

            $before = $target->roles()->pluck('name')->sort()->values()->all();
            $target->roles()->syncWithoutDetaching([$role->id]);
            $after = $target->roles()->pluck('name')->sort()->values()->all();

            $this->logger->record(
                'role_assigned',
                $actor,
                $justification,
                target: $target,
                role: $role,
                oldValues: ['roles' => $before],
                newValues: ['roles' => $after],
            );
        });
    }

    public function remove(User $actor, User $target, Role $role, string $justification): void
    {
        $this->authorizeRemove($actor, $target, $role);

        DB::transaction(function () use ($actor, $target, $role, $justification): void {
            $target->refresh();

            if (! $target->hasRole($role->name)) {
                return;
            }

            if ($target->roles()->count() <= 1) {
                throw new DomainException('O utilizador deve manter pelo menos uma role operacional.');
            }

            if ($role->name === 'administrator' && $this->isLastActiveAdministrator($target)) {
                throw new DomainException('Não é permitido remover o último administrator ativo.');
            }

            $before = $target->roles()->pluck('name')->sort()->values()->all();
            $target->roles()->detach($role->id);
            $after = $target->roles()->pluck('name')->sort()->values()->all();

            $this->logger->record(
                'role_removed',
                $actor,
                $justification,
                target: $target,
                role: $role,
                oldValues: ['roles' => $before],
                newValues: ['roles' => $after],
            );
        });
    }

    private function authorizeAssign(User $actor, User $target, Role $role): void
    {
        if (! $this->policy->assign($actor, $role)) {
            throw new AuthorizationException('Sem permissão para atribuir esta role.');
        }

        if ($actor->is($target)) {
            throw new AuthorizationException('Self-promotion bloqueado.');
        }

        if (! $this->roleIsWithinActorPermissions($actor, $role)) {
            throw new AuthorizationException('A role excede o escopo de permissões do actor.');
        }
    }

    private function authorizeRemove(User $actor, User $target, Role $role): void
    {
        if (! $this->policy->remove($actor, $role)) {
            throw new AuthorizationException('Sem permissão para remover esta role.');
        }

        if ($actor->is($target) && $role->name === 'administrator') {
            throw new AuthorizationException('Remoção insegura da própria role administrator bloqueada.');
        }
    }

    private function roleIsWithinActorPermissions(User $actor, Role $role): bool
    {
        if ($actor->hasPermission('*')) {
            return true;
        }

        return $role->permissions()
            ->pluck('name')
            ->every(fn (string $permission): bool => $actor->hasPermission($permission));
    }

    private function isLastActiveAdministrator(User $target): bool
    {
        if (! $target->hasRole('administrator') || $target->status !== 'active') {
            return false;
        }

        return ! User::query()
            ->whereKeyNot($target->id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->where('name', 'administrator'))
            ->exists();
    }
}
