<?php

namespace App\Http\Requests;

use App\Models\Arrear;
use App\Models\DefaultNotice;
use App\Models\PaymentReceipt;
use App\Models\RegularizationAgreement;
use Illuminate\Foundation\Http\FormRequest;

class CancelFinanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('arrear')
            ?? $this->route('defaultNotice')
            ?? $this->route('regularizationAgreement')
            ?? $this->route('paymentReceipt');

        if ($record instanceof Arrear) {
            return $this->user()?->can('resolveBackoffice', $record) === true;
        }

        return ($record instanceof DefaultNotice
                || $record instanceof RegularizationAgreement
                || $record instanceof PaymentReceipt)
            && $this->user()?->can('cancelBackoffice', $record) === true;
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
