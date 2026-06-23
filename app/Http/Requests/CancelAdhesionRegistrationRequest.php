<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelAdhesionRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $registration = $this->user()?->adhesionRegistration;

        return $registration !== null && $this->user()->can('cancel', $registration);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
