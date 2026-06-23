<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateManualAllocationRequest extends FormRequest
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
            'definitive_list_entry_id' => ['required', 'exists:definitive_list_entries,id'],
            'contest_housing_unit_id' => ['required', 'exists:contest_housing_units,id'],
            'manual_justification' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }
}
