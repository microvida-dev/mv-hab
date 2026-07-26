<?php

namespace App\Services\Access;

use App\Models\Role;
use Illuminate\Support\Facades\Config;

final class SystemRoleDefinitionRegistry
{
    /**
     * @return array<string, array{label: string, permissions: list<string>}>
     */
    public function all(): array
    {
        $configured = Config::get('mvhab.roles', []);

        if (! is_array($configured)) {
            return [];
        }

        $definitions = [];

        foreach ($configured as $name => $definition) {
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

    public function contains(string $identifier): bool
    {
        return array_key_exists($identifier, $this->all());
    }

    public function protects(Role $role): bool
    {
        return $role->isSystem() || $this->contains($role->name);
    }

    /** @return list<string> */
    public function permissionPatterns(string $identifier): array
    {
        return $this->all()[$identifier]['permissions'] ?? [];
    }
}
