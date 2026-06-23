<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserConsentRequest extends FormRequest
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
            'consent_purpose_id' => ['required', 'exists:consent_purposes,id'],
            'text_snapshot' => ['required', 'string', 'max:10000'],
        ];
    }
}
