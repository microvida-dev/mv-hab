<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentManualReviewRequest extends FormRequest
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
            'rent_calculation_id' => ['required', 'exists:rent_calculations,id'],
            'proposed_rent' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'min:10', 'max:5000'],
            'legal_basis' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
