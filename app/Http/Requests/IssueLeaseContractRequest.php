<?php

namespace App\Http\Requests;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;

class IssueLeaseContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contract = $this->route('leaseContract');

        return $contract instanceof Contract
            && ($this->user()?->can('issueBackoffice', $contract) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['issue_notes' => ['nullable', 'string', 'max:3000']];
    }
}
