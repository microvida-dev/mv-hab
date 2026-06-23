<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\LotteryParticipantStatus;
use App\Models\DrawAttendance;
use App\Models\LotteryDraw;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class DrawAttendanceService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function register(LotteryDraw $draw, array $data, User $actor): DrawAttendance
    {
        return DB::transaction(function () use ($draw, $data, $actor): DrawAttendance {
            $attendance = DrawAttendance::query()->firstOrNew([
                'lottery_run_id' => $draw->id,
                'application_id' => (int) $data['application_id'],
            ]);

            $attendance->fill([
                'draw_convocation_id' => $data['draw_convocation_id'] ?? null,
                'user_id' => (int) $data['user_id'],
                'lottery_participant_id' => $data['lottery_participant_id'] ?? null,
                'status' => $data['status'],
                'check_in_at' => in_array($data['status'], [AttendanceStatus::Present->value, AttendanceStatus::Late->value], true) ? now() : null,
                'justification' => $data['justification'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $attendance->forceFill(['registered_by' => $actor->id])->save();

            $participant = $attendance->participant ?? $draw->participants()->where('application_id', $attendance->application_id)->first();

            if ($participant !== null) {
                $participant->forceFill([
                    'status' => match ($attendance->status) {
                        AttendanceStatus::Present, AttendanceStatus::Late => LotteryParticipantStatus::Present,
                        AttendanceStatus::Justified => LotteryParticipantStatus::JustifiedAbsence,
                        AttendanceStatus::Absent => LotteryParticipantStatus::Absent,
                        default => $participant->status,
                    },
                    'present_at' => in_array($attendance->status, [AttendanceStatus::Present, AttendanceStatus::Late], true) ? now() : $participant->present_at,
                    'absent_at' => in_array($attendance->status, [AttendanceStatus::Absent, AttendanceStatus::Justified], true) ? now() : $participant->absent_at,
                ])->save();
            }

            $this->audit->record(AuditEvents::UPDATE, $attendance, 'allocations', 'draw_attendance_register', 'Presença em sorteio registada.');

            return $attendance->refresh();
        });
    }
}
