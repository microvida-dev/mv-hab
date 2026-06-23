<?php

namespace App\Models;

use App\Enums\ConvocationStatus;
use Database\Factories\DrawConvocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $lottery_run_id
 * @property int $contest_id
 * @property int $application_id
 * @property int $user_id
 * @property int|null $lottery_participant_id
 * @property ConvocationStatus $status
 * @property Carbon|null $scheduled_for
 */
class DrawConvocation extends Model
{
    /** @use HasFactory<DrawConvocationFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'generated_at', 'generated_by', 'sent_at', 'sent_by', 'delivered_at', 'read_at', 'failed_at', 'failure_reason', 'cancelled_at', 'cancelled_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => ConvocationStatus::class,
            'scheduled_for' => 'datetime',
            'generated_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<LotteryDraw, $this> */
    public function lotteryDraw(): BelongsTo
    {
        return $this->belongsTo(LotteryDraw::class, 'lottery_run_id');
    }

    /** @return BelongsTo<Contest, $this> */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
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
