<?php

namespace App\Http\Requests;

use App\Enums\ComplaintDecisionResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision_result' => ['required', 'string', Rule::in(ComplaintDecisionResult::values())],
            'summary' => ['required', 'string', 'max:3000'],
            'grounds' => ['required', 'string', 'min:10', 'max:10000'],
            'legal_basis' => ['nullable', 'string', 'max:3000'],
            'effects_on_ranking' => ['nullable', 'string', 'max:5000'],
            'effects_on_exclusion' => ['nullable', 'string', 'max:5000'],
            'requires_list_update' => ['boolean'],
            'candidate_visible' => ['boolean'],
        ];
    }
}
