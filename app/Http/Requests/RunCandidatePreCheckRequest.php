<?php

namespace App\Http\Requests;

use App\Models\EligibilityCheck;
use Illuminate\Foundation\Http\FormRequest;

class RunCandidatePreCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('runPreCheck', EligibilityCheck::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'program_id' => ['nullable', 'integer', 'exists:programs,id', 'required_without:contest_id'],
            'contest_id' => ['nullable', 'integer', 'exists:contests,id', 'required_without:program_id'],
        ];
    }
}
