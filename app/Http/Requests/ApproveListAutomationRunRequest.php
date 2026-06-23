<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveListAutomationRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('public_lists', 'approve') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['confirm_reviewed' => ['nullable', 'boolean']];
    }
}
