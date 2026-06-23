<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Database\Factories\DrawAttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $lottery_run_id
 * @property int|null $draw_convocation_id
 * @property int $application_id
 * @property int $user_id
 * @property int|null $lottery_participant_id
 * @property AttendanceStatus $status
 * @property Carbon|null $check_in_at
 */
class DrawAttendance extends Model
{
    /** @use HasFactory<DrawAttendanceFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'registered_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
            'check_in_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<LotteryDraw, $this> */
    public function lotteryDraw(): BelongsTo
    {
        return $this->belongsTo(LotteryDraw::class, 'lottery_run_id');
    }

    /** @return BelongsTo<DrawConvocation, $this> */
    public function convocation(): BelongsTo
    {
        return $this->belongsTo(DrawConvocation::class, 'draw_convocation_id');
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<User, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<LotteryParticipant, $this> */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(LotteryParticipant::class, 'lottery_participant_id');
    }
}
