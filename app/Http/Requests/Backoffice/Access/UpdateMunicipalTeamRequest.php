<?php

namespace App\Http\Requests\Backoffice\Access;

use App\Models\MunicipalTeam;
use App\Models\User;
use App\Policies\TeamManagementPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMunicipalTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $team = $this->route('municipalTeam');

        return $actor instanceof User
            && $team instanceof MunicipalTeam
            && app(TeamManagementPolicy::class)->update($actor, $team);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $team = $this->route('municipalTeam');
        $municipalityId = $this->user()?->municipality_id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('municipal_teams', 'name')->ignore($team)],
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
