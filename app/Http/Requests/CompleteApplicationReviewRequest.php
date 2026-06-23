<?php

namespace App\Http\Requests;

use App\Enums\ApplicationReviewResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteApplicationReviewRequest extends FormRequest
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
            'result' => ['required', 'string', Rule::in(ApplicationReviewResult::values())],
            'summary' => ['required', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
