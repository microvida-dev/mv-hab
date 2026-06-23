<?php

namespace App\Http\Requests;

use App\Enums\DataSubjectRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDataSubjectRequestRequest extends FormRequest
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
            'request_type' => ['required', Rule::in(DataSubjectRequestType::values())],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
            'requester_name' => ['nullable', 'string', 'max:255'],
            'requester_email' => ['nullable', 'email', 'max:255'],
            'requester_phone' => ['nullable', 'string', 'max:50'],
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
