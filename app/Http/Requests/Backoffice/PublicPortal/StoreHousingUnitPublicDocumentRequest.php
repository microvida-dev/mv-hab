<?php

namespace App\Http\Requests\Backoffice\PublicPortal;

use App\Enums\HousingUnitPublicDocumentType;
use App\Models\HousingUnitPublicDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreHousingUnitPublicDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows(
            'createBackoffice',
            HousingUnitPublicDocument::class,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'contest_id' => ['nullable', 'exists:contests,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'document_type' => ['required', Rule::in(HousingUnitPublicDocumentType::values())],
            'is_public' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }
}
