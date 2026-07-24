<?php

namespace App\Http\Requests;

use App\Models\ContractDeposit;
use Illuminate\Foundation\Http\FormRequest;

class WaiveContractDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        $deposit = $this->route('contractDeposit');

        return $deposit instanceof ContractDeposit
            && $this->user()?->can('waiveBackoffice', $deposit) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
