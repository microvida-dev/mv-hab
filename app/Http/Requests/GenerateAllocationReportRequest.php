<?php

namespace App\Http\Requests;

use App\Models\AllocationReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class GenerateAllocationReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows(
            'createBackoffice',
            AllocationReport::class,
        );
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
