<?php

namespace App\Http\Requests;

use App\Models\KeyHandoverAppointment;
use Illuminate\Foundation\Http\FormRequest;

class StoreKeyHandoverAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'scheduleBackoffice',
            KeyHandoverAppointment::class,
        ) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'winner_registration_id' => ['required', 'exists:winner_registrations,id'],
            'scheduled_for' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
