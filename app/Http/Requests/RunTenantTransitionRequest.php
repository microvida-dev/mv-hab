<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunTenantTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'winner_registration_id' => ['required', 'exists:winner_registrations,id'],
        ];
    }
}
