<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunProvisionalListAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('public_lists', 'create') || $this->user()?->hasPermissionTo('scoring', 'view');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contest_id' => ['nullable', 'exists:contests,id'],
            'confirm_snapshot_generation' => ['accepted'],
        ];
    }
}
