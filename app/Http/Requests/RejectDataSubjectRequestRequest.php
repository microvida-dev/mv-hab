<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectDataSubjectRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user()?->hasRole('candidate') && $this->user()?->hasPermission('privacy.reject');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:5', 'max:5000']];
    }
}
