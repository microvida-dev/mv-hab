<?php

namespace App\Http\Requests;

use App\Enums\ContractSignatureMethod;
use App\Enums\ContractSignatureRole;
use App\Models\Contract;
use App\Models\LeaseContractSignature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaseContractSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contract = $this->route('leaseContract');

        return $contract instanceof Contract
            && ($this->user()?->can(
                'signBackoffice',
                [LeaseContractSignature::class, $contract],
            ) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'signature_role' => ['required', Rule::enum(ContractSignatureRole::class)],
            'signed_by_name' => ['required', 'string', 'max:255'],
            'signed_at' => ['required', 'date'],
            'signature_method' => ['required', Rule::enum(ContractSignatureMethod::class)],
            'signature_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
