<?php

namespace App\Http\Requests;

use App\Models\HousingVisit;
use Illuminate\Foundation\Http\FormRequest;

class BookVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', HousingVisit::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'application_id' => ['nullable', 'exists:applications,id'],
            'contest_id' => ['nullable', 'exists:contests,id'],
            'housing_unit_id' => ['nullable', 'exists:housing_units,id'],
            'visit_slot_id' => ['required', 'exists:visit_slots,id'],
            'candidate_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
