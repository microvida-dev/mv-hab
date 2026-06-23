<?php

namespace App\Http\Requests\Backoffice;

use App\Models\DocumentAiValidationRun;
use Illuminate\Foundation\Http\FormRequest;

class RerunDocumentAiValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rerun', DocumentAiValidationRun::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirm_reprocess' => ['accepted'],
        ];
    }
}
