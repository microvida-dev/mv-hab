<?php

namespace App\Http\Requests\Backoffice\Access;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->routeIs('backoffice.users.roles.assign')
            ? 'roles.assign'
            : 'roles.remove';

        return (bool) $this->user()?->hasPermission($permission);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $roleExists = Rule::exists('roles', 'name');

        if ($this->routeIs('backoffice.users.roles.assign')) {
            $roleExists->where(fn ($query) => $query->where('is_active', true));
        }

        return [
            'role' => ['required', 'string', $roleExists],
            'justification' => ['required', 'string', 'max:1000'],
        ];
    }
}
