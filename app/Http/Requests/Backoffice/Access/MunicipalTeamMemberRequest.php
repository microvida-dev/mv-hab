<?php

namespace App\Http\Requests\Backoffice\Access;

use Illuminate\Foundation\Http\FormRequest;

class MunicipalTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('teams.manage_members');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role_in_team' => ['nullable', 'string', 'max:120'],
            'justification' => ['required', 'string', 'max:1000'],
        ];
    }
}
