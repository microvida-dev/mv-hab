<?php

namespace App\Http\Requests;

use App\Models\KeyHandoverAppointment;

class UpdateKeyHandoverAppointmentRequest extends StoreKeyHandoverAppointmentRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('keyHandoverAppointment');

        return $appointment instanceof KeyHandoverAppointment
            && ($this->user()?->can('updateBackoffice', $appointment) ?? false);
    }

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
