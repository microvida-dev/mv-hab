<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesRequiredDocumentConfiguration;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequiredDocumentRequest extends FormRequest
{
    use ValidatesRequiredDocumentConfiguration;

    public function authorize(): bool
    {
        return $this->user()?->can(
            'updateBackoffice',
            $this->route('requiredDocument'),
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
