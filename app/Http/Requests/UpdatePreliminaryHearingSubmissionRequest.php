<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreliminaryHearingSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'review_notes' => ['nullable', 'string', 'max:5000'],
            'accepted' => ['required', 'boolean'],
        ];
    }
}
