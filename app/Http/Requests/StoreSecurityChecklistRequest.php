<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSecurityChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user()?->hasRole('candidate') && $this->user()?->hasPermission('settings.audit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['environment' => ['nullable', 'string', 'max:80']];
    }
}
