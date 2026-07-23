<?php

namespace App\Http\Requests\Backoffice\Access;

use App\Models\MunicipalTeam;
use App\Models\User;
use App\Policies\TeamManagementPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MunicipalTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $team = $this->route('municipalTeam');

        return $actor instanceof User
            && $team instanceof MunicipalTeam
            && app(TeamManagementPolicy::class)->manageMembers($actor, $team);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $municipalityId = $this->user()?->municipality_id;

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('municipality_id', $municipalityId),
            ],
            'role_in_team' => ['nullable', 'string', 'max:120'],
            'justification' => ['required', 'string', 'max:1000'],
        ];
    }
}
