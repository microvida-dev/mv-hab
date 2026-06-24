<?php

namespace App\Http\Requests\Backoffice\Access;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMunicipalTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('teams.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $team = $this->route('municipalTeam');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('municipal_teams', 'name')->ignore($team)],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'functional_scopes' => ['nullable'],
            'manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'justification' => ['required', 'string', 'max:1000'],
        ];
    }
}
