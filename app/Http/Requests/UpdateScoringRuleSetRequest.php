<?php

namespace App\Http\Requests;

use App\Enums\ScoringRuleSetStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateScoringRuleSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('scoringRuleSet')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'program_id' => ['nullable', 'required_without:contest_id', 'exists:programs,id'],
            'contest_id' => ['nullable', 'required_without:program_id', 'exists:contests,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', 'in:'.implode(',', ScoringRuleSetStatus::values())],
            'is_default' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
