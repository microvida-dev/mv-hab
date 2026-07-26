<?php

namespace App\Http\Requests;

use App\Enums\AttendanceStatus;
use Illuminate\Validation\Rule;

class BulkRegisterDrawAttendanceRequest extends RegisterDrawAttendanceRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.application_id' => ['required', $this->participantApplicationExistsRule()],
            'attendances.*.user_id' => ['required', $this->participantUserExistsRule()],
            'attendances.*.lottery_participant_id' => ['nullable', $this->participantExistsRule()],
            'attendances.*.draw_convocation_id' => ['nullable', $this->convocationExistsRule()],
            'attendances.*.status' => ['required', Rule::in(AttendanceStatus::values())],
            'attendances.*.justification' => ['nullable', 'string', 'max:3000'],
            'attendances.*.notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
