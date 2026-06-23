<?php

namespace App\Http\Requests\Backoffice;

use App\Models\DocumentAiSuggestion;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentAiSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $suggestion = $this->route('suggestion');

        return $suggestion instanceof DocumentAiSuggestion
            && ($this->user()?->can('update', $suggestion) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'suggestion' => ['required', 'string', 'min:20', 'max:4000'],
        ];
    }
}
