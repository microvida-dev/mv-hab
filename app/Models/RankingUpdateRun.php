<?php

namespace App\Models;

use App\Enums\RankingUpdateStatus;
use Database\Factories\RankingUpdateRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $lottery_run_id
 * @property int $contest_id
 * @property RankingUpdateStatus $status
 * @property array<string, mixed>|null $before_snapshot
 * @property array<string, mixed>|null $after_snapshot
 */
class RankingUpdateRun extends Model
{
    /** @use HasFactory<RankingUpdateRunFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'applied_at', 'applied_by', 'reviewed_at', 'reviewed_by', 'approved_at', 'approved_by', 'reverted_at', 'reverted_by', 'failed_at', 'failure_reason', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => RankingUpdateStatus::class,
            'before_snapshot' => 'array',
            'after_snapshot' => 'array',
            'summary' => 'array',
            'applied_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'reverted_at' => 'datetime',
            'failed_at' => 'datetime',
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
}
