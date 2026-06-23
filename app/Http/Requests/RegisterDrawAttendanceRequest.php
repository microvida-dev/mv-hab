<?php

namespace App\Http\Requests;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterDrawAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'application_id' => ['required', 'exists:applications,id'],
            'user_id' => ['required', 'exists:users,id'],
            'lottery_participant_id' => ['nullable', 'exists:lottery_participants,id'],
            'draw_convocation_id' => ['nullable', 'exists:draw_convocations,id'],
            'status' => ['required', Rule::in(AttendanceStatus::values())],
            'justification' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
