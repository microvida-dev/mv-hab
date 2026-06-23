<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseContestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'allow_pending' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
