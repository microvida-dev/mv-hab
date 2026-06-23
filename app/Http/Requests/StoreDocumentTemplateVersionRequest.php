<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentTemplateVersionRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
            'html_body' => ['nullable', 'string', 'max:100000'],
            'header' => ['nullable', 'string', 'max:10000'],
            'footer' => ['nullable', 'string', 'max:10000'],
            'variables_schema' => ['nullable', 'array'],
            'change_summary' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
