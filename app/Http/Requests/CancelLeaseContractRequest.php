<?php

namespace App\Http\Requests;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;

class CancelLeaseContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contract = $this->route('leaseContract');

        return $contract instanceof Contract
            && ($this->user()?->can('cancelBackoffice', $contract) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:3000']];
    }
}
