<?php

namespace App\Http\Requests;

use App\Enums\CorrectionIssueType;
use App\Enums\CorrectionRequiredAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCorrectionRequestRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'legal_basis' => ['nullable', 'string', 'max:3000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'response_deadline_at' => ['nullable', 'date', 'after:now'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:3000'],
            'items.*.issue_type' => ['required', 'string', Rule::in(CorrectionIssueType::values())],
            'items.*.required_action' => ['required', 'string', Rule::in(CorrectionRequiredAction::values())],
            'items.*.is_required' => ['nullable', 'boolean'],
            'items.*.document_type_id' => ['nullable', 'integer', 'exists:document_types,id'],
            'items.*.required_document_id' => ['nullable', 'integer', 'exists:required_documents,id'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }
}
