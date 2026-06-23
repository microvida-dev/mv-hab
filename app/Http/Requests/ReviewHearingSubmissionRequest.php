<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewHearingSubmissionRequest extends FormRequest
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
            'accepted' => ['nullable', 'boolean'],
            'review_result' => ['nullable', 'string', 'max:100'],
            'review_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
