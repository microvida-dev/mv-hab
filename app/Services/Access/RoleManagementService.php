<?php

namespace App\Services\Access;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleManagementService
{
    public function __construct(
        private readonly AccessChangeLogger $logger,
        private readonly RolePolicy $policy,
        private readonly SystemRoleDefinitionRegistry $systemRoles,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $permissionIds
     */
    public function create(User $actor, array $data, array $permissionIds, string $justification): Role
    {
        if (! $this->policy->create($actor)) {
            throw new AuthorizationException('Sem permissão para criar perfis municipais.');
        }

        return DB::transaction(function () use ($actor, $data, $permissionIds, $justification): Role {
            $permissions = $this->authorizedPermissions($actor, $permissionIds);
            $role = $this->persistRole($data, $permissions);
            $permissionNames = $permissions->pluck('name')->sort()->values()->all();

            $this->logger->record(
                'role_created',
                $actor,
                $justification,
                role: $role,
                newValues: [
                    'role' => $this->snapshot($role),
                    'permissions_added' => $permissionNames,
                    'permissions_removed' => [],
                    'affected_user_count' => 0,
                ],
            );

            return $role->load('permissions');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $permissionIds
     */
    public function update(User $actor, Role $role, array $data, array $permissionIds, string $justification): Role
    {
        if (! $this->policy->update($actor, $role)) {
            throw new AuthorizationException('Sem permissão para alterar este perfil municipal.');
        }

        return DB::transaction(function () use ($actor, $role, $data, $permissionIds, $justification): Role {
            $lockedRole = $this->lock($role);
            $this->assertMutable($lockedRole);
            $permissions = $this->authorizedPermissions($actor, $permissionIds);
            $beforePermissions = $lockedRole->permissions()->pluck('name')->sort()->values()->all();
            $before = $this->snapshot($lockedRole);
            $description = $data['description'] ?? null;

            $lockedRole->forceFill([
                'label' => trim((string) $data['label']),
                'description' => $this->nullableTrim(is_string($description) ? $description : null),
            ])->save();
            $lockedRole->permissions()->sync($permissions->modelKeys());

            $afterPermissions = $permissions->pluck('name')->sort()->values()->all();
            $added = array_values(array_diff($afterPermissions, $beforePermissions));
            $removed = array_values(array_diff($beforePermissions, $afterPermissions));

            $this->logger->record(
                'role_updated',
                $actor,
                $justification,
                role: $lockedRole,
                oldValues: [
                    'role' => $before,
                    'permissions' => $beforePermissions,
                ],
                newValues: [
                    'role' => $this->snapshot($lockedRole),
                    'permissions' => $afterPermissions,
                    'permissions_added' => $added,
                    'permissions_removed' => $removed,
                    'affected_user_count' => $lockedRole->users()->count(),
                ],
            );

            return $lockedRole->load('permissions');
        });
    }

    public function duplicate(
        User $actor,
        Role $source,
        string $label,
        ?string $description,
        string $justification,
    ): Role {
        if (! $this->policy->duplicate($actor, $source)) {
            throw new AuthorizationException('Sem permissão para duplicar este perfil.');
        }

        return DB::transaction(function () use ($actor, $source, $label, $description, $justification): Role {
            $lockedSource = $this->lock($source);
            $permissionIds = $lockedSource->permissions()
                ->pluck('permissions.id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();
            $permissions = $this->authorizedPermissions($actor, $permissionIds);
            $role = $this->persistRole([
                'label' => $label,
                'description' => $description,
            ], $permissions);

            $this->logger->record(
                'role_duplicated',
                $actor,
                $justification,
                role: $role,
                newValues: [
                    'source_role_id' => $lockedSource->id,
                    'source_role_name' => $lockedSource->name,
                    'role' => $this->snapshot($role),
                    'permissions_added' => $permissions->pluck('name')->sort()->values()->all(),
                    'permissions_removed' => [],
                    'affected_user_count' => 0,
                ],
            );

            return $role->load('permissions');
        });
    }

    public function activate(User $actor, Role $role, string $justification): Role
    {
        return $this->setActive($actor, $role, true, $justification);
    }

    public function deactivate(User $actor, Role $role, string $justification): Role
    {
        return $this->setActive($actor, $role, false, $justification);
    }

    public function delete(User $actor, Role $role, string $justification): void
    {
        if (! $this->policy->delete($actor, $role)) {
            throw new AuthorizationException('Sem permissão para eliminar este perfil municipal.');
        }

        DB::transaction(function () use ($actor, $role, $justification): void {
            $lockedRole = $this->lock($role);
            $this->assertMutable($lockedRole);
            $affectedUsers = $lockedRole->users()->count();

            if ($affectedUsers > 0) {
                throw new DomainException('O perfil não pode ser eliminado enquanto tiver utilizadores associados.');
            }

            $snapshot = $this->snapshot($lockedRole);
            $permissions = $lockedRole->permissions()->pluck('name')->sort()->values()->all();

            $this->logger->record(
                'role_deleted',
                $actor,
                $justification,
                role: $lockedRole,
                oldValues: [
                    'role' => $snapshot,
                    'permissions' => $permissions,
                    'affected_user_count' => 0,
                ],
            );

            $lockedRole->delete();
        });
    }

    private function setActive(User $actor, Role $role, bool $active, string $justification): Role
    {
        if (! $this->policy->toggle($actor, $role)) {
            throw new AuthorizationException('Sem permissão para alterar o estado deste perfil municipal.');
        }

        return DB::transaction(function () use ($actor, $role, $active, $justification): Role {
            $lockedRole = $this->lock($role);
            $this->assertMutable($lockedRole);

            if ($lockedRole->isActive() === $active) {
                return $lockedRole;
            }

            $before = $this->snapshot($lockedRole);
            $affectedUsers = $lockedRole->users()->count();
            $lockedRole->forceFill(['is_active' => $active])->save();

            $this->logger->record(
                $active ? 'role_activated' : 'role_deactivated',
                $actor,
                $justification,
                role: $lockedRole,
                oldValues: ['role' => $before],
                newValues: [
                    'role' => $this->snapshot($lockedRole),
                    'affected_user_count' => $affectedUsers,
                ],
            );

            return $lockedRole;
        });
    }

    private function lock(Role $role): Role
    {
        return Role::query()
            ->where('id', (int) $role->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertMutable(Role $role): void
    {
        if ($this->systemRoles->protects($role)) {
            throw new DomainException('Os perfis de sistema são apenas de leitura.');
        }

        if (! $role->isMunicipalCustom()) {
            throw new DomainException('Apenas perfis municipais personalizados podem ser alterados.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  Collection<int, Permission>  $permissions
     */
    private function persistRole(array $data, Collection $permissions): Role
    {
        $label = trim((string) $data['label']);
        $description = $data['description'] ?? null;

        $role = Role::query()->create([
            'name' => $this->uniqueIdentifier($label),
            'label' => $label,
            'description' => $this->nullableTrim(is_string($description) ? $description : null),
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);

        $role->permissions()->sync($permissions->modelKeys());

        return $role;
    }

    /**
     * @param  array<int, int>  $permissionIds
     * @return Collection<int, Permission>
     */
    private function authorizedPermissions(User $actor, array $permissionIds): Collection
    {
        $ids = collect($permissionIds)->map(fn ($id): int => (int) $id)->unique()->values();
        $permissions = Permission::query()->whereKey($ids->all())->get();

        if ($permissions->count() !== $ids->count()) {
            throw new DomainException('Uma ou mais permissões selecionadas não existem.');
        }

        if (! $actor->hasPermission('*')) {
            $outsideActorScope = $permissions->contains(
                fn (Permission $permission): bool => ! $actor->hasPermission($permission->name),
            );

            if ($outsideActorScope) {
                throw new AuthorizationException('Não pode conceder permissões superiores às suas.');
            }
        }

        return $permissions;
    }

    private function uniqueIdentifier(string $label): string
    {
        $base = Str::slug(trim($label), '_');
        $base = $base !== '' ? Str::limit($base, 100, '') : 'perfil_municipal';
        $candidate = $base;
        $suffix = 2;

        while (Role::query()->where('name', $candidate)->exists()) {
            $candidate = $base.'_'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }

    /** @return array<string, bool|int|string|null> */
    private function snapshot(Role $role): array
    {
        return [
            'id' => (int) $role->id,
            'name' => $role->name,
            'label' => $role->label,
            'description' => $role->description,
            'scope' => $role->scope,
            'is_system' => $role->isSystem(),
            'is_active' => $role->isActive(),
        ];
    }
}
