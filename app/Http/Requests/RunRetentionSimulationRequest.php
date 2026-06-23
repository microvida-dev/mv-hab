<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunRetentionSimulationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user()?->hasRole('candidate') && $this->user()?->hasPermission('privacy.audit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
