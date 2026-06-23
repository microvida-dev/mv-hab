<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkRegisterDrawAttendanceRequest;
use App\Http\Requests\RegisterDrawAttendanceRequest;
use App\Models\DrawAttendance;
use App\Models\LotteryDraw;
use App\Services\Attendance\AttendanceSummaryService;
use App\Services\Attendance\DrawAttendanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DrawAttendanceController extends Controller
{
    public function __construct(
        private readonly DrawAttendanceService $attendances,
        private readonly AttendanceSummaryService $summary,
    ) {}

    public function index(LotteryDraw $lotteryDraw): View
    {
        Gate::authorize('viewAny', DrawAttendance::class);

        $lotteryDraw->load(['participants.candidate', 'convocations', 'attendances.candidate']);

        return view('backoffice.lottery-draws.attendance', [
            'lotteryDraw' => $lotteryDraw,
            'summary' => $this->summary->summarize($lotteryDraw),
        ]);
    }

    public function store(RegisterDrawAttendanceRequest $request, LotteryDraw $lotteryDraw): RedirectResponse
    {
        Gate::authorize('create', DrawAttendance::class);

        $this->attendances->register($lotteryDraw, $request->validated(), $this->authenticatedUser($request));

        return back()->with('success', 'Presença registada.');
    }

    public function bulkStore(BulkRegisterDrawAttendanceRequest $request, LotteryDraw $lotteryDraw): RedirectResponse
    {
        Gate::authorize('create', DrawAttendance::class);

        foreach ($request->validated('attendances') as $attendance) {
            $this->attendances->register($lotteryDraw, $attendance, $this->authenticatedUser($request));
        }

        return back()->with('success', 'Presenças registadas.');
    }
}
