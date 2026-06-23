<?php

namespace App\Http\Requests\Backoffice;

use App\Models\DocumentAiAnalysis;
use App\Policies\DocumentAiAssistantPolicy;
use Illuminate\Foundation\Http\FormRequest;

class RecalculateDocumentAiScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $analysis = $this->route('analysis');

        return $analysis instanceof DocumentAiAnalysis
            && $this->user() !== null
            && app(DocumentAiAssistantPolicy::class)->recalculate($this->user(), $analysis);
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
