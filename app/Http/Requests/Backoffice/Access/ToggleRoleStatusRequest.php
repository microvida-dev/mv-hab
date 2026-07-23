<?php

namespace App\Http\Requests\Backoffice\Access;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class ToggleRoleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role && (bool) $this->user()?->can('toggle', $role);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'justification' => ['required', 'string', 'max:1000', 'not_regex:/<[^>]*>/'],
        ];
    }
}
