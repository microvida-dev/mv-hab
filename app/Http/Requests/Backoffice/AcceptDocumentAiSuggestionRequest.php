<?php

namespace App\Http\Requests\Backoffice;

use App\Models\DocumentAiSuggestion;
use Illuminate\Foundation\Http\FormRequest;

class AcceptDocumentAiSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $suggestion = $this->route('suggestion');

        return $suggestion instanceof DocumentAiSuggestion
            && ($this->user()?->can('accept', $suggestion) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirm_accept' => ['accepted'],
        ];
    }
}
