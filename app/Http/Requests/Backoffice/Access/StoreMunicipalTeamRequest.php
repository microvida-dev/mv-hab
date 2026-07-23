<?php

namespace App\Http\Requests\Backoffice\Access;

use App\Models\User;
use App\Policies\TeamManagementPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMunicipalTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof User
            && app(TeamManagementPolicy::class)->create($actor);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $municipalityId = $this->user()?->municipality_id;

        return [
            'name' => ['required', 'string', 'max:255', 'unique:municipal_teams,name'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'functional_scopes' => ['nullable'],
            'manager_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('municipality_id', $municipalityId),
            ],
            'justification' => ['required', 'string', 'max:1000'],
        ];
    }
}
