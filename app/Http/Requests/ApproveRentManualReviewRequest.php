<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveRentManualReviewRequest extends FormRequest
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
            'approved_rent' => ['nullable', 'numeric', 'min:0'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
