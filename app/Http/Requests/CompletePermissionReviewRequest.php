<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompletePermissionReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('settings.audit') || $this->user()?->hasPermission('audit_logs.audit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['summary' => ['nullable', 'string', 'max:5000']];
    }
}
