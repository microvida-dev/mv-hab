<?php

namespace App\Http\Requests;

use App\Models\KeyHandoverAppointment;
use Illuminate\Foundation\Http\FormRequest;

class CompleteKeyHandoverAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('keyHandoverAppointment');

        return $appointment instanceof KeyHandoverAppointment
            && ($this->user()?->can('completeBackoffice', $appointment) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
