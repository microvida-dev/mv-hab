<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteDataSubjectRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user()?->hasRole('candidate') && $this->user()?->hasPermission('privacy.approve');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['summary' => ['required', 'string', 'min:5', 'max:5000']];
    }
}
