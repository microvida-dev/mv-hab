<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateDrawConvocationsRequest extends FormRequest
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
            'scheduled_for' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
