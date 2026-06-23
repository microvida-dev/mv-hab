<?php

namespace App\Http\Requests;

use App\Enums\ApplicationReviewResult;
use App\Enums\ApplicationReviewType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationReviewRequest extends FormRequest
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
            'review_type' => ['required', 'string', Rule::in(ApplicationReviewType::values())],
            'summary' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['nullable', 'array'],
            'items.*.code' => ['nullable', 'string', 'max:150'],
            'items.*.name' => ['required_with:items', 'string', 'max:255'],
            'items.*.category' => ['nullable', 'string', 'max:100'],
            'items.*.result' => ['nullable', 'string', Rule::in(ApplicationReviewResult::values())],
            'items.*.message' => ['nullable', 'string', 'max:3000'],
            'items.*.technical_message' => ['nullable', 'string', 'max:3000'],
            'items.*.requires_correction' => ['nullable', 'boolean'],
            'items.*.correction_reason' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
