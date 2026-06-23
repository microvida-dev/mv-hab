<?php

namespace App\Http\Requests;

use App\Models\AdhesionRegistration;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCandidateHouseholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user instanceof User) {
            return false;
        }

        $registration = $user->adhesionRegistration()->first();

        if (! $registration instanceof AdhesionRegistration) {
            return false;
        }

        $household = $registration->household()->first();

        return $household instanceof Household && $user->can('update', $household);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'household_type' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
