<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHouseholdRequest extends FormRequest
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
            'citizen_id' => ['required', 'exists:citizens,id'],
            'name' => ['required', 'string', 'max:255'],
            'monthly_income' => ['required', 'numeric', 'min:0'],
            'members_count' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
