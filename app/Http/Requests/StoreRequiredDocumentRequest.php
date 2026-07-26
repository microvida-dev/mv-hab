<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesRequiredDocumentConfiguration;
use App\Models\RequiredDocument;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequiredDocumentRequest extends FormRequest
{
    use ValidatesRequiredDocumentConfiguration;

    public function authorize(): bool
    {
        return $this->user()?->can(
            'createBackoffice',
            RequiredDocument::class,
        ) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->requiredDocumentRules();
    }
}
