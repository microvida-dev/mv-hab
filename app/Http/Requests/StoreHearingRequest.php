<?php

namespace App\Http\Requests;

use App\Enums\HearingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHearingRequest extends FormRequest
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
            'application_id' => ['required', 'exists:applications,id'],
            'provisional_list_id' => ['nullable', 'exists:provisional_lists,id'],
            'definitive_list_id' => ['nullable', 'exists:definitive_lists,id'],
            'hearing_type' => ['required', 'string', Rule::in(HearingType::values())],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'legal_basis' => ['nullable', 'string', 'max:3000'],
            'grounds' => ['required', 'string', 'max:10000'],
            'deadline_at' => ['required', 'date', 'after:now'],
            'candidate_visible' => ['boolean'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
