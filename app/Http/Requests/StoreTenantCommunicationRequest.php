<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantCommunicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'lease_contract_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'subject' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'body' => ['required', 'string', 'max:10000'],
            'category' => ['nullable', 'string', 'max:80'],
            'priority' => ['nullable', 'string', 'max:80'],
            'visibility' => ['nullable', 'string', 'max:80'],
        ];
    }
}
