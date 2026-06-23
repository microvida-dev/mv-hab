<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DisableMfaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('settings.update') || $this->user()?->hasRole('administrator');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['reason' => ['nullable', 'string', 'max:1000']];
    }
}
