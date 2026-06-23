<?php

namespace App\Http\Requests;

use App\Enums\AnonymizationMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateDefinitiveListRequest extends FormRequest
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
            'provisional_list_id' => ['required', 'exists:provisional_lists,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'publication_starts_at' => ['nullable', 'date'],
            'publication_ends_at' => ['nullable', 'date', 'after_or_equal:publication_starts_at'],
            'anonymization_mode' => ['nullable', 'string', Rule::in(AnonymizationMode::values())],
            'public_visibility' => ['required', 'boolean'],
            'legal_basis' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
