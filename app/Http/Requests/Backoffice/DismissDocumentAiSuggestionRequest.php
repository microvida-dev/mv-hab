<?php

namespace App\Http\Requests\Backoffice;

use App\Models\DocumentAiSuggestion;
use Illuminate\Foundation\Http\FormRequest;

class DismissDocumentAiSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $suggestion = $this->route('suggestion');

        return $suggestion instanceof DocumentAiSuggestion
            && ($this->user()?->can('dismiss', $suggestion) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dismiss_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
