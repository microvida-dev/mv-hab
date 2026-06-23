<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
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
            'citizen_id' => ['nullable', 'exists:citizens,id'],
            'housing_application_id' => ['nullable', 'exists:housing_applications,id'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'name' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ];
    }
}
