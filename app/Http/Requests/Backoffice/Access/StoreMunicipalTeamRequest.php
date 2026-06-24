<?php

namespace App\Http\Requests\Backoffice\Access;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMunicipalTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('teams.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:municipal_teams,name'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'functional_scopes' => ['nullable'],
            'manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'justification' => ['required', 'string', 'max:1000'],
        ];
    }
}
