<?php

namespace App\Http\Requests\Simulator;

use Illuminate\Foundation\Http\FormRequest;

class ConvertSimulationToPrefillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('candidate') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
            'confirm_indicative_result' => ['accepted'],
        ];
    }
}
