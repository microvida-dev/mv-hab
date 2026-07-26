<?php

namespace App\Http\Requests;

use App\Enums\RetentionAction;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRetentionPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->municipality_id !== null
            && $user->hasPermission('rgpd.retention.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'entity_type' => ['required', 'string', 'max:255'],
            'document_type_id' => ['nullable', 'integer', 'exists:document_types,id'],
            'retention_period_months' => ['required', 'integer', 'min:0', 'max:1200'],
            'retention_action' => ['required', Rule::in(RetentionAction::values())],
            'legal_basis' => ['nullable', 'string', 'max:3000'],
            'requires_manual_approval' => ['sometimes', 'boolean'],
        ];
    }
}
