<?php

namespace App\Services\Cases;

use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class CaseAuthorizationService
{
    public function __construct(private readonly CaseTypeRegistry $registry) {}

    public function canViewCase(User $user, Model $case): bool
    {
        if ($case instanceof Application) {
            return Gate::forUser($user)->allows('view', $case) && ! $user->hasRole('candidate');
        }

        return false;
    }

    public function canViewEnterpriseCase(User $user, string $caseType, Model $case): bool
    {
        if ($user->hasRole('candidate')) {
            return false;
        }

        $config = $this->registry->get($caseType);
        $permission = $config['permission'] ?? null;

        if (! is_string($permission) || ! $this->hasPermission($user, $permission)) {
            return false;
        }

        return Gate::forUser($user)->allows('view', $case);
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

    public function canMutateCases(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']);
    }
}
