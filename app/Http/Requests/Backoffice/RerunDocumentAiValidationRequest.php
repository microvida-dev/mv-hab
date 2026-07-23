<?php

namespace App\Http\Requests\Backoffice;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class RerunDocumentAiValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof Application
            && ($this->user()?->can('analyzeDocumentsBackoffice', $application) ?? false);
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
