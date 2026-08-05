<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Access;

use App\Models\Role;
use App\Models\User;
use App\Policies\RoleAssignmentPolicy;
use App\Services\Access\AccessMunicipalScopeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $target = $this->route('user');

        if (! $actor instanceof User
            || ! $target instanceof User
            || ! app(AccessMunicipalScopeService::class)->ownsUser($actor, $target)) {
            return false;
        }

        $role = app(AccessMunicipalScopeService::class)
            ->roles(Role::query(), $actor)
            ->where('name', (string) $this->input('role'))
            ->first();

        if (! $role instanceof Role) {
            return false;
        }

        if ($this->routeIs('backoffice.users.roles.assign')) {
            return ! $actor->hasRole('candidate')
                && $actor->hasPermission('roles.assign');
        }

        return app(RoleAssignmentPolicy::class)->remove($actor, $role);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $actor = $this->user();
        $municipalityId = $actor instanceof User
            ? app(AccessMunicipalScopeService::class)->municipalityId($actor)
            : null;
        $roleExists = Rule::exists('roles', 'name')
            ->where(fn ($query) => $query->where(fn ($roles) => $roles
                ->where('is_system', true)
                ->orWhere('municipality_id', $municipalityId)));

        if ($this->routeIs('backoffice.users.roles.assign')) {
            $roleExists->where(fn ($query) => $query->where('is_active', true));
        }

        return [
            'role' => ['required', 'string', $roleExists],
            'justification' => ['required', 'string', 'max:1000'],
        ];
    }
}
