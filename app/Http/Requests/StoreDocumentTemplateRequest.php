<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentTemplateRequest extends FormRequest
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
        $template = $this->route('documentTemplate');
        $codeRule = Rule::unique('document_templates', 'code')
            ->where(fn ($query) => $query
                ->where('program_id', $this->input('program_id'))
                ->where('contest_id', $this->input('contest_id'))
                ->whereNull('deleted_at'))
            ->ignore($template);

        return [
            'code' => ['required', 'string', 'max:150', $codeRule],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'category' => ['required', 'string', 'max:150'],
            'language' => ['required', 'string', 'max:10'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
            'html_body' => ['nullable', 'string', 'max:100000'],
            'header' => ['nullable', 'string', 'max:10000'],
            'footer' => ['nullable', 'string', 'max:10000'],
            'is_official' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'requires_approval' => ['sometimes', 'boolean'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'contest_id' => ['nullable', 'exists:contests,id'],
        ];
    }
}
