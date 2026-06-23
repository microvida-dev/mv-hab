<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateProcessConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('applications', 'update') || $this->user()?->hasPermissionTo('applications', 'approve');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'application_id' => ['nullable', 'exists:applications,id'],
            'force_regenerate' => ['nullable', 'boolean'],
        ];
    }
}
