<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Enums\FeatureKey;
use App\Models\Municipality;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Services\Entitlements\MunicipalityEntitlementService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** @phpstan-import-type ResolvedMunicipalTemplate from MunicipalRoleTemplateRegistry */
class RoleManagementService
{
    public function __construct(
        private readonly AccessChangeLogger $logger,
        private readonly RolePolicy $policy,
        private readonly SystemRoleDefinitionRegistry $systemRoles,
        private readonly MunicipalRoleTemplateRegistry $templates,
        private readonly PermissionCatalogService $permissionCatalog,
        private readonly MunicipalityEntitlementService $entitlements,
        private readonly AccessMunicipalScopeService $municipalScope,
    ) {}

    /**
     * @return array{
     *     municipality: array{id: int, name: string},
     *     template: array<string, mixed>,
     *     role: Role|null,
     *     permissions_to_add: list<string>,
     *     permissions_to_keep: list<string>,
     *     permissions_to_remove: list<string>,
     *     missing_entitlements: list<array{key: string, label: string}>,
     *     mfa_required: bool,
     *     conflicts: list<string>,
     *     permissions_drift: bool,
     *     metadata_drift: bool,
     *     presentation_drift: bool,
     *     drift: bool
     * }
     */
    public function previewTemplate(User $actor, string $templateKey): array
    {
        $municipality = $this->templateMunicipality($actor);
        $template = $this->templates->resolve($templateKey);
        $role = Role::query()
            ->with('permissions:id,name')
            ->where('municipality_id', $municipality->id)
            ->where('template_key', $templateKey)
            ->first();
        $diff = $this->templateDiff($template, $role);
        $missingEntitlements = $this->missingEntitlements($actor, $template);
        $mfaRequired = $this->templateRequiresMfa($template);

        $this->logger->record(
            'municipal_role_template_previewed',
            $actor,
            'Pré-visualização da matriz de um template municipal.',
            role: $role,
            newValues: [
                'municipality_id' => (int) $municipality->id,
                'template_key' => $template['key'],
                'template_version' => $template['version'],
                'template_fingerprint' => $template['fingerprint'],
                'permissions_to_add' => $diff['permissions_to_add'],
                'permissions_to_keep_count' => count($diff['permissions_to_keep']),
                'permissions_to_remove' => $diff['permissions_to_remove'],
                'entitlement_dependencies' => $template['entitlement_dependencies'],
                'missing_entitlement_count' => count($missingEntitlements),
                'mfa_required' => $mfaRequired,
                'drift' => $diff['drift'],
            ],
        );

        return [
            'municipality' => [
                'id' => (int) $municipality->id,
                'name' => $municipality->name,
            ],
            'template' => $template,
            'role' => $role,
            ...$diff,
            'missing_entitlements' => $missingEntitlements,
            'mfa_required' => $mfaRequired,
            'conflicts' => [],
        ];
    }

    public function applyTemplate(
        User $actor,
        string $templateKey,
        string $justification,
        bool $confirmReconcile = false,
    ): Role {
        $municipality = $this->templateMunicipality($actor);
        $template = $this->templates->resolve($templateKey);
        $permissions = $this->authorizedPermissions($actor, $template['permission_ids']);

        /** @var array{role: Role, blocked: bool} $result */
        $result = DB::transaction(function () use (
            $actor,
            $municipality,
            $template,
            $permissions,
            $justification,
            $confirmReconcile,
        ): array {
            $lockedMunicipality = Municipality::query()
                ->whereKey($municipality->id)
                ->lockForUpdate()
                ->firstOrFail();
            $role = Role::query()
                ->with('permissions:id,name')
                ->where('municipality_id', $lockedMunicipality->id)
                ->where('template_key', $template['key'])
                ->lockForUpdate()
                ->first();

            if (! $role instanceof Role) {
                $identifier = $this->templateIdentifier(
                    (int) $lockedMunicipality->id,
                    $template['key'],
                );

                if (Role::query()->where('name', $identifier)->exists()) {
                    throw new DomainException(
                        'Já existe um perfil municipal com o identificador reservado para este template.'
                    );
                }

                $role = Role::query()->create([
                    'municipality_id' => $lockedMunicipality->id,
                    'template_key' => $template['key'],
                    'template_version' => $template['version'],
                    'template_fingerprint' => $template['fingerprint'],
                    'name' => $identifier,
                    'label' => $template['label'],
                    'description' => $template['description'],
                    'scope' => 'municipal',
                    'is_system' => false,
                    'is_active' => true,
                ]);
                $role->permissions()->sync($permissions->modelKeys());

                $this->logger->record(
                    'municipal_role_template_created',
                    $actor,
                    $justification,
                    role: $role,
                    newValues: [
                        'municipality_id' => (int) $lockedMunicipality->id,
                        'template_key' => $template['key'],
                        'template_version' => $template['version'],
                        'template_fingerprint' => $template['fingerprint'],
                        'permissions_added' => $template['permissions'],
                        'permissions_removed' => [],
                        'permission_count' => count($template['permissions']),
                        'entitlement_dependencies' => $template['entitlement_dependencies'],
                        'affected_user_count' => 0,
                    ],
                );

                return ['role' => $role->load('permissions'), 'blocked' => false];
            }

            $this->assertMutable($role);
            $diff = $this->templateDiff($template, $role);

            if ($diff['drift'] && ! $confirmReconcile) {
                $this->logger->record(
                    'municipal_role_template_drift_detected',
                    $actor,
                    $justification,
                    role: $role,
                    oldValues: [
                        'template_version' => $role->template_version,
                        'template_fingerprint' => $role->template_fingerprint,
                        'permission_count' => $role->permissions->count(),
                    ],
                    newValues: [
                        'template_key' => $template['key'],
                        'template_version' => $template['version'],
                        'template_fingerprint' => $template['fingerprint'],
                        'permissions_to_add' => $diff['permissions_to_add'],
                        'permissions_to_remove' => $diff['permissions_to_remove'],
                    ],
                );

                return ['role' => $role, 'blocked' => true];
            }

            if (! $diff['drift']) {
                return ['role' => $role, 'blocked' => false];
            }

            $before = $this->snapshot($role);
            $role->forceFill([
                'label' => $template['label'],
                'description' => $template['description'],
                'template_version' => $template['version'],
                'template_fingerprint' => $template['fingerprint'],
            ])->save();
            $role->permissions()->sync($permissions->modelKeys());

            $this->logger->record(
                'municipal_role_template_reconciled',
                $actor,
                $justification,
                role: $role,
                oldValues: [
                    'role' => $before,
                    'permissions' => $diff['current_permissions'],
                ],
                newValues: [
                    'role' => $this->snapshot($role),
                    'permissions' => $template['permissions'],
                    'permissions_added' => $diff['permissions_to_add'],
                    'permissions_removed' => $diff['permissions_to_remove'],
                    'affected_user_count' => $role->users()->count(),
                ],
            );

            return ['role' => $role->load('permissions'), 'blocked' => false];
        });

        if ($result['blocked']) {
            throw new DomainException(
                'A matriz atual diverge do template. Reveja o preview e confirme explicitamente a reconciliação.'
            );
        }

        return $result['role'];
    }

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
            $role = $this->persistRole($actor, $data, $permissions);
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

    /** @param array<string, mixed> $data */
    public function updateDetails(User $actor, Role $role, array $data, string $justification): Role
    {
        $permissionIds = array_values($role->permissions()
            ->pluck('permissions.id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all());

        return $this->update($actor, $role, $data, $permissionIds, $justification);
    }

    /** @param list<int> $permissionIds */
    public function synchronizePermissions(
        User $actor,
        Role $role,
        array $permissionIds,
        string $justification,
    ): Role {
        return $this->update($actor, $role, [
            'label' => $role->label,
            'description' => $role->description,
        ], $permissionIds, $justification);
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
            $role = $this->persistRole($actor, [
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
    private function persistRole(User $actor, array $data, Collection $permissions): Role
    {
        $label = trim((string) $data['label']);
        $description = $data['description'] ?? null;

        $role = Role::query()->create([
            'municipality_id' => $this->municipalScope->municipalityId($actor),
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

    private function templateMunicipality(User $actor): Municipality
    {
        if (! $this->policy->create($actor)) {
            throw new AuthorizationException('Sem permissão para aplicar templates municipais.');
        }
        $municipality = $this->municipalScope->requireMunicipality($actor);

        return $municipality;
    }

    /**
     * @param  ResolvedMunicipalTemplate  $template
     * @return array{
     *     current_permissions: list<string>,
     *     permissions_to_add: list<string>,
     *     permissions_to_keep: list<string>,
     *     permissions_to_remove: list<string>,
     *     permissions_drift: bool,
     *     metadata_drift: bool,
     *     presentation_drift: bool,
     *     drift: bool
     * }
     */
    private function templateDiff(array $template, ?Role $role): array
    {
        $current = $role instanceof Role
            ? array_values($role->permissions->pluck('name')->map(fn (mixed $name): string => (string) $name)->sort()->values()->all())
            : [];
        $expected = $template['permissions'];
        sort($expected, SORT_STRING);
        $toAdd = array_values(array_diff($expected, $current));
        $toKeep = array_values(array_intersect($expected, $current));
        $toRemove = array_values(array_diff($current, $expected));
        $permissionsDrift = $role instanceof Role && ($toAdd !== [] || $toRemove !== []);
        $metadataDrift = $role instanceof Role && (
            $role->template_version !== $template['version']
            || $role->template_fingerprint !== $template['fingerprint']
        );
        $presentationDrift = $role instanceof Role && (
            $role->label !== $template['label']
            || $role->description !== $template['description']
        );

        return [
            'current_permissions' => $current,
            'permissions_to_add' => $toAdd,
            'permissions_to_keep' => $toKeep,
            'permissions_to_remove' => $toRemove,
            'permissions_drift' => $permissionsDrift,
            'metadata_drift' => $metadataDrift,
            'presentation_drift' => $presentationDrift,
            'drift' => $permissionsDrift || $metadataDrift || $presentationDrift,
        ];
    }

    /**
     * @param  ResolvedMunicipalTemplate  $template
     * @return list<array{key: string, label: string}>
     */
    private function missingEntitlements(User $actor, array $template): array
    {
        $municipality = $this->municipalScope->requireMunicipality($actor);
        $missing = [];

        foreach ($template['entitlement_dependencies'] as $dependency) {
            $feature = FeatureKey::tryFrom((string) $dependency);

            if (! $feature instanceof FeatureKey) {
                throw new DomainException('O template declara um entitlement desconhecido: '.$dependency.'.');
            }

            if (! $this->entitlements->enabledFor($municipality, $feature)) {
                $missing[] = [
                    'key' => $feature->value,
                    'label' => $feature->label(),
                ];
            }
        }

        return $missing;
    }

    /** @param ResolvedMunicipalTemplate $template */
    private function templateRequiresMfa(array $template): bool
    {
        foreach ($template['permissions'] as $permission) {
            if ($this->permissionCatalog->isSensitive($permission)) {
                return true;
            }
        }

        return false;
    }

    private function templateIdentifier(int $municipalityId, string $templateKey): string
    {
        return Str::limit(
            'municipal_'.$municipalityId.'_'.Str::slug($templateKey, '_'),
            180,
            '',
        );
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
            'template_key' => $role->template_key,
            'template_version' => $role->template_version,
            'template_fingerprint' => $role->template_fingerprint,
        ];
    }
}
