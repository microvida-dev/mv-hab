<?php

namespace App\Services\Dashboard;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class DashboardAuthorizationService
{
    /** @var array<int, array<int, string>> */
    private array $roleNamesByUser = [];

    /** @var array<int, array<int, string>> */
    private array $permissionNamesByUser = [];

    public function isActive(User $user): bool
    {
        return $user->deactivated_at === null
            && $user->status === 'active';
    }

    public function hasPermission(User $user, string $permission): bool
    {
        [$module, $action] = str_contains($permission, '.')
            ? explode('.', $permission, 2)
            : [$permission, null];

        foreach ($this->permissionNames($user) as $permissionName) {
            if ($permissionName === '*'
                || $permissionName === $permission
                || $permissionName === $module.'.*'
                || ($action !== null && $permissionName === '*.'.$action)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $roles
     */
    public function hasAnyRole(User $user, array $roles): bool
    {
        return array_intersect($roles, $this->roleNames($user)) !== [];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function canSeeItem(User $user, array $item): bool
    {
        $route = $item['route'] ?? null;
        if (is_string($route) && ! Route::has($route)) {
            return false;
        }

        $permission = $item['permission'] ?? null;
        if (is_string($permission) && ! $this->hasPermission($user, $permission)) {
            return false;
        }

        $roles = $item['roles'] ?? null;
        if (is_array($roles) && ! $this->hasAnyRole($user, array_values(array_filter($roles, 'is_string')))) {
            return false;
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public function profileKeys(User $user): array
    {
        $ordered = [
            'administrator',
            'municipal_technician',
            'jury',
            'legal_manager',
            'financial_manager',
            'housing_manager',
            'maintenance_manager',
            'inspection_manager',
            'support_agent',
            'auditor',
            'candidate',
        ];

        return array_values(array_filter($ordered, fn (string $role): bool => $this->hasAnyRole($user, [$role])));
    }

    public function primaryProfile(User $user): string
    {
        return $this->profileKeys($user)[0] ?? 'municipal_technician';
    }

    public function profileLabel(User $user): string
    {
        return match ($this->primaryProfile($user)) {
            'administrator' => 'Administração municipal',
            'municipal_technician' => 'Técnico municipal',
            'jury' => 'Júri',
            'legal_manager' => 'Gestão jurídica',
            'financial_manager' => 'Gestão financeira',
            'housing_manager' => 'Gestão habitacional',
            'maintenance_manager' => 'Manutenção',
            'inspection_manager' => 'Vistorias',
            'support_agent' => 'Atendimento',
            'auditor' => 'Auditoria',
            'candidate' => 'Candidato',
            default => 'Backoffice municipal',
        };
    }

    /**
     * @return array<int, string>
     */
    private function roleNames(User $user): array
    {
        if (! array_key_exists((int) $user->id, $this->roleNamesByUser)) {
            $user->loadMissing('roles.permissions');
            $this->roleNamesByUser[(int) $user->id] = $user->roles
                ->pluck('name')
                ->filter(fn (mixed $name): bool => is_string($name))
                ->values()
                ->all();
        }

        return $this->roleNamesByUser[(int) $user->id];
    }

    /**
     * @return array<int, string>
     */
    private function permissionNames(User $user): array
    {
        if (! array_key_exists((int) $user->id, $this->permissionNamesByUser)) {
            $user->loadMissing('roles.permissions');
            $this->permissionNamesByUser[(int) $user->id] = $user->roles
                ->flatMap(fn ($role) => $role->permissions->pluck('name'))
                ->filter(fn (mixed $name): bool => is_string($name))
                ->unique()
                ->values()
                ->all();
        }

        return $this->permissionNamesByUser[(int) $user->id];
    }
}
