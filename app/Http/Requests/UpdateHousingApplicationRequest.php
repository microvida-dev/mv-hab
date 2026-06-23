<?php

namespace App\Http\Requests;

use App\Enums\HousingApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHousingApplicationRequest extends FormRequest
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
            'household_id' => ['nullable', 'exists:households,id'],
            'status' => ['required', Rule::enum(HousingApplicationStatus::class)],
            'priority_score' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'submitted_at' => ['nullable', 'date'],
        ];
    }
}
