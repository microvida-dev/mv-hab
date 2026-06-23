<?php

namespace App\Http\Requests;

use App\Enums\AllocationStatus;
use App\Models\Allocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CalculateRentRequest extends FormRequest
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
            'allocation_id' => ['required', 'exists:allocations,id'],
            'rent_rule_set_id' => ['nullable', 'exists:rent_rule_sets,id'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $allocation = Allocation::query()->find($this->input('allocation_id'));

                if (! $allocation) {
                    return;
                }

                if (! in_array($allocation->status, [AllocationStatus::Accepted, AllocationStatus::ReadyForContract], true)) {
                    $validator->errors()->add('allocation_id', 'A atribuição deve estar aceite ou pronta para contrato.');
                }
            },
        ];
    }
}
