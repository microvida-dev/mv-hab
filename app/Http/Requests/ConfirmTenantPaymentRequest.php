<?php

namespace App\Http\Requests;

use App\Models\TenantPayment;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmTenantPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $payment = $this->route('tenantPayment');

        return $payment instanceof TenantPayment
            && $this->user()?->can('confirmBackoffice', $payment) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
