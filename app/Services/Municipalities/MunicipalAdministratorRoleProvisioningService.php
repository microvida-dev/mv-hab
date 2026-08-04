<?php

namespace App\Services\Municipalities;

use App\Models\Municipality;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

final class MunicipalAdministratorRoleProvisioningService
{
    public function __construct(
        private readonly MunicipalRoleTemplateRegistry $templates,
        private readonly MunicipalityOnboardingPlanner $planner,
    ) {}

    public function provision(Municipality $municipality): Role
    {
        $template = $this->templates->resolve(MunicipalityOnboardingPlanner::TEMPLATE_KEY);
        $identifier = $this->planner->roleIdentifier((int) $municipality->id);

        $role = Role::query()
            ->where('municipality_id', $municipality->id)
            ->where('template_key', $template['key'])
            ->lockForUpdate()
            ->first();

        if ($role instanceof Role) {
            if ($role->template_version !== $template['version']
                || $role->template_fingerprint !== $template['fingerprint']) {
                throw new DomainException(
                    'A role administrativa municipal existente diverge do template aprovado.',
                );
            }

            $current = $role->permissions()->pluck('name')->sort()->values()->all();
            $expected = collect($template['permissions'])->sort()->values()->all();

            if ($current !== $expected) {
                throw new DomainException(
                    'A matriz da role administrativa municipal existente diverge do template aprovado.',
                );
            }

            return $role;
        }

        if (Role::query()->where('name', $identifier)->exists()) {
            throw new DomainException(
                'O identificador reservado para a role administrativa municipal já está ocupado.',
            );
        }

        $role = Role::query()->create([
            'municipality_id' => $municipality->id,
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

        /** @var Collection<int, Permission> $permissions */
        $permissions = Permission::query()
            ->whereIn('id', $template['permission_ids'])
            ->get();

        if ($permissions->count() !== count($template['permission_ids'])) {
            throw new DomainException('A matriz administrativa contém permissões indisponíveis.');
        }

        $role->permissions()->sync($permissions->modelKeys());

        return $role->load('permissions');
    }
}
