<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\LotteryDraw;

class AttendanceSummaryService
{
    /**
     * @return array{total:int, present:int, absent:int, justified:int, pending:int}
     */
    public function summarize(LotteryDraw $draw): array
    {
        $attendances = $draw->attendances()->get();

        return [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', AttendanceStatus::Present)->count(),
            'absent' => $attendances->where('status', AttendanceStatus::Absent)->count(),
            'justified' => $attendances->where('status', AttendanceStatus::Justified)->count(),
            'pending' => $attendances->where('status', AttendanceStatus::Pending)->count(),
        ];
    }
}
