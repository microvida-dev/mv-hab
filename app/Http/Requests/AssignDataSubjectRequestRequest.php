<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignDataSubjectRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user()?->hasRole('candidate') && $this->user()?->hasPermission('privacy.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['assigned_to' => ['required', 'exists:users,id']];
    }
}
