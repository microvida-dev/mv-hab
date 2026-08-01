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
        private readonly AccessMunicipalScopeService $municipalScope,
        private readonly MunicipalRoleTemplateRegistry $templates,
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

        if (! $role->isActive()) {
            throw new DomainException('Não é possível atribuir um perfil inativo.');
        }

        if ($target->status !== 'active') {
            throw new DomainException('Não é possível atribuir um perfil a uma conta inativa.');
        }

        if ($target->hasRole('candidate') && $role->isMunicipalCustom()) {
            throw new AuthorizationException('Uma conta de candidato não pode receber perfis municipais internos.');
        }

        if ($this->createsAuditorMutationConflict($target, $role)) {
            throw new AuthorizationException('O perfil de auditor não pode ser combinado com um perfil mutável do Programa 53.');
        }

        if (! $this->municipalScope->ownsUser($actor, $target)) {
            throw new AuthorizationException('Atribuição entre municípios bloqueada.');
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

        if (! $this->municipalScope->ownsUser($actor, $target)) {
            throw new AuthorizationException('Remoção entre municípios bloqueada.');
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

    private function createsAuditorMutationConflict(User $target, Role $role): bool
    {
        if ($target->hasRole('auditor') && $this->isProgram53MutableRole($role)) {
            return true;
        }

        if ($role->name !== 'auditor') {
            return false;
        }

        return $target->roles()
            ->active()
            ->whereNotNull('template_key')
            ->get([
                'roles.id',
                'roles.template_key',
                'roles.template_version',
                'roles.template_fingerprint',
                'roles.scope',
                'roles.is_system',
            ])
            ->contains(fn (Role $assignedRole): bool => $this->isProgram53MutableRole($assignedRole));
    }

    private function isProgram53MutableRole(Role $role): bool
    {
        if (! $role->isTemplateBased() || ! is_string($role->template_key)) {
            return false;
        }

        try {
            return $this->templates->isProgram53Mutable($role->template_key);
        } catch (DomainException) {
            return false;
        }
    }

    private function isLastActiveAdministrator(User $target): bool
    {
        if (! $target->hasRole('administrator') || $target->status !== 'active') {
            return false;
        }

        return ! User::query()
            ->whereKeyNot($target->id)
            ->where('municipality_id', $target->municipality_id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->where('name', 'administrator'))
            ->exists();
    }
}
