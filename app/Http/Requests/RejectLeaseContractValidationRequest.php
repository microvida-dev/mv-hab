<?php

namespace App\Http\Requests;

use App\Models\LeaseContractValidation;
use Illuminate\Foundation\Http\FormRequest;

class RejectLeaseContractValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $validation = $this->route('leaseContractValidation');

        return $validation instanceof LeaseContractValidation
            && ($this->user()?->can('rejectBackoffice', $validation) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['rejection_reason' => ['required', 'string', 'min:10', 'max:3000']];
    }
}
