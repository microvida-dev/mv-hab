<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBackupReviewRequest extends FormRequest
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
        return [
            'environment' => ['nullable', 'string', 'max:80'],
            'backup_scope' => ['nullable', 'string', 'max:5000'],
            'frequency' => ['nullable', 'string', 'max:255'],
            'retention_period' => ['nullable', 'string', 'max:255'],
            'findings' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
