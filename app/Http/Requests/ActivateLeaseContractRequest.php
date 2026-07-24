<?php

namespace App\Http\Requests;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;

class ActivateLeaseContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contract = $this->route('leaseContract');

        return $contract instanceof Contract
            && ($this->user()?->can('activateBackoffice', $contract) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'activation_reason' => ['nullable', 'string', 'max:3000'],
            'confirm_activation' => ['accepted'],
        ];
    }
}
