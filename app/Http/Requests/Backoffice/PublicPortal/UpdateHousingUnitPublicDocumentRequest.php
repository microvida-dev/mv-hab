<?php

namespace App\Http\Requests\Backoffice\PublicPortal;

use App\Enums\HousingUnitPublicDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHousingUnitPublicDocumentRequest extends FormRequest
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
            'contest_id' => ['nullable', 'exists:contests,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'document_type' => ['required', Rule::in(HousingUnitPublicDocumentType::values())],
            'is_public' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }
}
