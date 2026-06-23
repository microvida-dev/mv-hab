<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveInternalAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('backoffice.update') || $this->user()?->hasPermission('reports.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
