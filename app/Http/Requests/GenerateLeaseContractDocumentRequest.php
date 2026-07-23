<?php

namespace App\Http\Requests;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;

class GenerateLeaseContractDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contract = $this->route('leaseContract');

        return $contract instanceof Contract
            && $this->user()?->can('generateBackoffice', $contract) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['document_type' => ['nullable', 'string', 'max:100']];
    }
}
