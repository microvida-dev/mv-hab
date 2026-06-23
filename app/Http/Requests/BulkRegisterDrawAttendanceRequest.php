<?php

namespace App\Http\Requests;

class BulkRegisterDrawAttendanceRequest extends RegisterDrawAttendanceRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.application_id' => ['required', 'exists:applications,id'],
            'attendances.*.user_id' => ['required', 'exists:users,id'],
            'attendances.*.lottery_participant_id' => ['nullable', 'exists:lottery_participants,id'],
            'attendances.*.draw_convocation_id' => ['nullable', 'exists:draw_convocations,id'],
            'attendances.*.status' => ['required', 'string'],
            'attendances.*.justification' => ['nullable', 'string', 'max:3000'],
            'attendances.*.notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
