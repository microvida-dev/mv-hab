<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHousingPreferenceRequest extends FormRequest
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
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*.contest_housing_unit_id' => ['required', 'exists:contest_housing_units,id'],
            'preferences.*.preference_order' => ['required', 'integer', 'min:1'],
            'preferences.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
