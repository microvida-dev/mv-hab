<?php

namespace App\Http\Requests;

use App\Models\TenantCommunication;
use Illuminate\Foundation\Http\FormRequest;

class StoreTenantCommunicationMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $communication = $this->route('tenantCommunication');

        if (! $communication instanceof TenantCommunication) {
            return false;
        }

        return $this->routeIs('backoffice.*')
            ? ($this->user()?->can('messageBackoffice', $communication) ?? false)
            : ($this->user()?->can('update', $communication) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
            'visible_to_tenant' => ['sometimes', 'boolean'],
        ];
    }
}
