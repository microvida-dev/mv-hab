<?php

namespace App\Http\Requests\Backoffice;

use App\Models\DocumentAiAnalysis;
use Illuminate\Foundation\Http\FormRequest;

class RecalculateDocumentAiScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $analysis = $this->route('analysis');

        return $analysis instanceof DocumentAiAnalysis
            && ($this->user()?->can('analyzeBackoffice', $analysis) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirm_recalculate' => ['accepted'],
        ];
    }
}
