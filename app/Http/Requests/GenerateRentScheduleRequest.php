<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateRentScheduleRequest extends FormRequest
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
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0.01'],
            'payment_day' => ['nullable', 'integer', 'between:1,28'],
            'issue_day' => ['nullable', 'integer', 'between:1,28'],
            'due_grace_days' => ['nullable', 'integer', 'between:0,30'],
            'schedule_type' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
