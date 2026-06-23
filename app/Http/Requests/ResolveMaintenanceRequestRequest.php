<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveMaintenanceRequestRequest extends FormRequest
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
            'resolution_summary' => ['required', 'string', 'min:10', 'max:5000'],
            'closure_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
