<?php

namespace App\Http\Requests\Reporting;

use Illuminate\Foundation\Http\FormRequest;

class DashboardFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->hasRole('candidate');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'contest_id' => ['nullable', 'integer', 'exists:contests,id'],
            'status' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:150'],
        ];
    }
}
