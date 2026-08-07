<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertRegulatoryRentLimitTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateBackoffice', $this->route('regulatoryProfile')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rent_rule_set_id' => ['required', 'integer', 'exists:rent_rule_sets,id'],
            'source_document' => ['required', 'string', 'max:2000'],
            'source_reference' => ['required', 'string', 'max:255'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'rows' => ['required', 'array', 'min:1', 'max:30'],
            'rows.*.typology' => ['nullable', 'string', 'max:40'],
            'rows.*.minimum_rent' => ['nullable', 'numeric', 'min:0'],
            'rows.*.maximum_rent' => ['nullable', 'numeric', 'min:0'],
            'rows.*.source_row_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
