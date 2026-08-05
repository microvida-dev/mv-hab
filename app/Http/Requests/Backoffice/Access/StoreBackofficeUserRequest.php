<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Access;

use App\Models\User;
use App\Policies\UserAdministrationPolicy;
use App\Services\Access\AccessMunicipalScopeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBackofficeUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof User
            && app(UserAdministrationPolicy::class)->create($actor);
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->where(fn ($roles) => $roles
                        ->where('is_system', true)
                        ->orWhere('municipality_id', $municipalityId))),
            ],
            'team_id' => [
                'nullable',
                'integer',
                Rule::exists('municipal_teams', 'id')->where('municipality_id', $municipalityId),
            ],
            'role_in_team' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'mfa_required' => ['nullable', 'boolean'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'justification' => ['required', 'string', 'max:1000'],
        ];
    }
}
