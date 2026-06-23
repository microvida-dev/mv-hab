<?php

namespace App\Http\Requests;

class UpdateKeyHandoverAppointmentRequest extends StoreKeyHandoverAppointmentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scheduled_for' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
