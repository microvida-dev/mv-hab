<?php

namespace App\Http\Requests;

use App\Enums\ContractValidationType;
use App\Models\Contract;
use App\Models\LeaseContractValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateLeaseContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        $validation = $this->route('leaseContractValidation');

        if ($validation instanceof LeaseContractValidation) {
            return $this->user()?->can('approveBackoffice', $validation) ?? false;
        }

        $contract = $this->route('leaseContract');

        return $contract instanceof Contract
            && ($this->user()?->can(
                'validateBackoffice',
                [LeaseContractValidation::class, $contract],
            ) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'validation_type' => ['required', Rule::enum(ContractValidationType::class)],
            'summary' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
