<?php

namespace App\Http\Requests\Backoffice;

use App\Enums\DocumentAiRiskFlagCode;
use App\Enums\DocumentAiScoreLabel;
use App\Models\DocumentAiScore;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterDocumentAiAssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', DocumentAiScore::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['nullable', Rule::enum(DocumentAiScoreLabel::class)],
            'flag' => ['nullable', Rule::enum(DocumentAiRiskFlagCode::class)],
            'requires_review' => ['nullable', 'boolean'],
            'application' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
