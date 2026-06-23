<?php

namespace App\Http\Requests;

use App\Models\AdhesionRegistration;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class FinalizeAdhesionRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user instanceof User) {
            return false;
        }

        $registration = $user->adhesionRegistration;

        return $registration instanceof AdhesionRegistration && $user->can('finalize', $registration);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'nif' => ['required', 'string', 'max:20'],
            'birth_date' => ['required', 'date', 'before_or_equal:'.today()->subYears(18)->toDateString()],
            'address' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:100'],
            'municipality' => ['required', 'string', 'max:100'],
            'accepts_terms' => ['accepted'],
            'accepts_data_processing' => ['accepted'],

        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        $user = $this->user();

        if (! $user instanceof User) {
            return [];
        }

        $registration = $user->adhesionRegistration;

        if (! $registration instanceof AdhesionRegistration) {
            return [];
        }

        return [
            ...$registration->only([
                'full_name',
                'email',
                'nif',
                'address',
                'postal_code',
                'city',
                'municipality',
                'accepts_terms',
                'accepts_data_processing',
            ]),
            'birth_date' => $registration->birth_date
                ? Carbon::parse($registration->birth_date)->toDateString()
                : null,
        ];
    }
}
