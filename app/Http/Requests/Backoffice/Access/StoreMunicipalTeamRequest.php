<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Access;

use App\Models\User;
use App\Policies\TeamManagementPolicy;
use App\Services\Access\AccessMunicipalScopeService;
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
        $actor = $this->user();
        $municipalityId = $actor instanceof User
            ? app(AccessMunicipalScopeService::class)->municipalityId($actor)
            : null;

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
