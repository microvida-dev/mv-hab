<?php

namespace App\Http\Requests;

use App\Models\VisitAvailability;
use Illuminate\Foundation\Http\FormRequest;

class StoreVisitAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', VisitAvailability::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contest_id' => ['nullable', 'exists:contests,id'],
            'housing_unit_id' => ['nullable', 'exists:housing_units,id'],
            'staff_user_id' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'slot_duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'capacity_per_slot' => ['required', 'integer', 'min:1', 'max:100'],
            'buffer_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'is_active' => ['boolean'],
        ];
    }
}
