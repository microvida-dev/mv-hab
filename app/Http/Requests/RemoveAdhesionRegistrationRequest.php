<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemoveAdhesionRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $registration = $this->user()?->adhesionRegistration;

        return $registration !== null && $this->user()->can('delete', $registration);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirm_removal' => ['accepted'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
