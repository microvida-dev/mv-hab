<?php

namespace App\Services\Cases;

use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class CaseAuthorizationService
{
    public function canViewCase(User $user, Model $case): bool
    {
        if ($case instanceof Application) {
            return Gate::forUser($user)->allows('view', $case) && ! $user->hasRole('candidate');
        }

        return false;
    }

    public function hasPermission(User $user, string $permission): bool
    {
        return $user->hasPermission($permission)
            || $user->hasPermission('*')
            || (str_contains($permission, '.') && $user->hasPermission(str($permission)->before('.')->toString().'.*'));
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
        if (is_array($roles) && ! $user->hasRole(array_values(array_filter($roles, 'is_string')))) {
            return false;
        }

        return true;
    }
}
