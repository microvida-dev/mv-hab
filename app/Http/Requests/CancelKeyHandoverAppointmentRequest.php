<?php

namespace App\Http\Requests;

use App\Models\KeyHandoverAppointment;
use Illuminate\Foundation\Http\FormRequest;

class CancelKeyHandoverAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('keyHandoverAppointment');

        return $appointment instanceof KeyHandoverAppointment
            && ($this->user()?->can('cancelBackoffice', $appointment) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:3000'],
        ];
    }
}
