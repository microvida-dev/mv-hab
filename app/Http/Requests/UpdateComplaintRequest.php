<?php

namespace App\Http\Requests;

class UpdateComplaintRequest extends StoreComplaintRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'grounds' => ['required', 'string', 'min:10', 'max:10000'],
            'requested_outcome' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
