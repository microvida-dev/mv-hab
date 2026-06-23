<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAllocationReportRequest extends FormRequest
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
            'allocation_run_id' => ['required', 'exists:allocation_runs,id'],
            'legal_basis' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
