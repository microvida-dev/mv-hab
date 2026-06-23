<?php

namespace App\Http\Requests;

use App\Models\ScoringRun;
use Illuminate\Foundation\Http\FormRequest;

class RunScoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ScoringRun::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'program_id' => ['nullable', 'required_without:contest_id', 'exists:programs,id'],
            'contest_id' => ['nullable', 'required_without:program_id', 'exists:contests,id'],
            'scoring_rule_set_id' => ['nullable', 'exists:scoring_rule_sets,id'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
