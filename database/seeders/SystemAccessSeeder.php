<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class SystemAccessSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect($this->permissionDefinitions())
            ->flatMap(function (array $actions, string $module) {
                return collect($actions)->map(fn (string $action) => [
                    'name' => $module.'.'.$action,
                    'module' => $module,
                    'action' => $action,
                    'description' => null,
                ]);
            });

        $permissions->each(fn (array $permission) => Permission::query()->updateOrCreate(
            ['name' => $permission['name']],
            $permission,
        ));

        Permission::query()->updateOrCreate(
            ['name' => '*'],
            [
                'module' => '*',
                'action' => '*',
                'description' => 'Full system access.',
            ],
        );

        foreach ($this->roleDefinitions() as $name => $definition) {
            $role = Role::query()->updateOrCreate(
                ['name' => $name],
                [
                    'label' => $definition['label'],
                    'scope' => 'system',
                    'is_system' => true,
                ],
            );

            $permissionIds = collect($definition['permissions'])
                ->flatMap(function (string $permission) {
                    if ($permission === '*') {
                        return Permission::query()->where('name', '*')->pluck('id');
                    }

                    if (str_ends_with($permission, '.*')) {
                        $module = Str::before($permission, '.*');

                        return Permission::query()->where('module', $module)->pluck('id');
                    }

                    if (str_starts_with($permission, '*.')) {
                        $action = Str::after($permission, '*.');

                        return Permission::query()->where('action', $action)->pluck('id');
                    }

                    return Permission::query()->where('name', $permission)->pluck('id');
                })
                ->unique()
                ->values()
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function permissionDefinitions(): array
    {
        $config = Config::get('mvhab.permissions', []);

        if (! is_array($config)) {
            return [];
        }

        $definitions = [];
        foreach ($config as $module => $actions) {
            if (! is_string($module) || ! is_array($actions)) {
                continue;
            }

            $definitions[$module] = array_values(array_filter($actions, 'is_string'));
        }

        return $definitions;
    }

    /**
     * @return array<string, array{label: string, permissions: list<string>}>
     */
    private function roleDefinitions(): array
    {
        $config = Config::get('mvhab.roles', []);

        if (! is_array($config)) {
            return [];
        }

        $definitions = [];
        foreach ($config as $name => $definition) {
            if (! is_string($name) || ! is_array($definition)) {
                continue;
            }

            $label = $definition['label'] ?? null;
            $permissions = $definition['permissions'] ?? null;

            if (! is_string($label) || ! is_array($permissions)) {
                continue;
            }

            $definitions[$name] = [
                'label' => $label,
                'permissions' => array_values(array_filter($permissions, 'is_string')),
            ];
        }

        return $definitions;
    }
}
