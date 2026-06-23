<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuspendLeaseContractRequest extends FormRequest
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
        return ['reason' => ['required', 'string', 'min:10', 'max:3000']];
    }
}
