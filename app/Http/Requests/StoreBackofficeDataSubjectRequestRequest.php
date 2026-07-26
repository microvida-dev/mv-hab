<?php

namespace App\Http\Requests;

use App\Enums\DataSubjectRequestType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBackofficeDataSubjectRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->municipality_id !== null
            && $user->hasPermission('privacy.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $municipalityId = $this->user()?->municipality_id;

        return [
            'request_type' => ['required', Rule::in(DataSubjectRequestType::values())],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
            'requester_name' => ['nullable', 'string', 'max:255'],
            'requester_email' => ['nullable', 'email', 'max:255'],
            'requester_phone' => ['nullable', 'string', 'max:50'],
            'user_id' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where('municipality_id', $municipalityId),
            ],
        ];
    }
}
