<?php

namespace App\Http\Requests;

use App\Models\LeasePayment;
use Illuminate\Foundation\Http\FormRequest;

class ReverseLeasePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $payment = $this->route('leasePayment');

        return $payment instanceof LeasePayment
            && $this->user()?->can('reverseBackoffice', $payment) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:3000'],
        ];
    }
}
