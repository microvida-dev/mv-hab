<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\ActorProfile;
use App\Enums\FeatureKey;
use App\Models\User;
use App\Services\Entitlements\MunicipalityEntitlementService;
use App\Services\Platform\ActorProfileResolver;
use Illuminate\Support\Facades\Route;

class DashboardAuthorizationService
{
    /** @var array<int, array<int, string>> */
    private array $roleNamesByUser = [];

    /** @var array<int, array<int, string>> */
    private array $permissionNamesByUser = [];

    public function __construct(
        private readonly MunicipalityEntitlementService $entitlements,
        private readonly ActorProfileResolver $profiles,
    ) {}

    public function isActive(User $user): bool
    {
        return $user->deactivated_at === null
            && $user->status === 'active';
    }

    public function actorProfile(User $user): ActorProfile
    {
        return $this->profiles->primary($user);
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

        $feature = $item['feature'] ?? null;
        if ($feature instanceof FeatureKey && ! $this->entitlements->enabledForUser($user, $feature)) {
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
        return array_map(
            static fn (ActorProfile $profile): string => $profile->dashboardKey(),
            $this->profiles->profiles($user),
        );
    }

    public function primaryProfile(User $user): string
    {
        return $this->actorProfile($user)->dashboardKey();
    }

    public function profileLabel(User $user): string
    {
        return $this->actorProfile($user)->label();
    }

    /**
     * @return array<int, string>
     */
    private function roleNames(User $user): array
    {
        if (! array_key_exists((int) $user->id, $this->roleNamesByUser)) {
            $user->loadMissing('roles.permissions');
            $this->roleNamesByUser[(int) $user->id] = $user->roles
                ->where('is_active', true)
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
                ->where('is_active', true)
                ->flatMap(fn ($role) => $role->permissions->pluck('name'))
                ->filter(fn (mixed $name): bool => is_string($name))
                ->unique()
                ->values()
                ->all();
        }

        return $this->permissionNamesByUser[(int) $user->id];
    }
}
