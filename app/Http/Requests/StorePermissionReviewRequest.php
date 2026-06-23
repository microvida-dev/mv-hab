<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionReviewRequest extends FormRequest
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
        return ['scope' => ['nullable', 'string', 'max:150']];
    }
}
