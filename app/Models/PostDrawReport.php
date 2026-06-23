<?php

namespace App\Models;

use Database\Factories\PostDrawReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $lottery_run_id
 * @property int $contest_id
 * @property string $report_number
 * @property string $status
 * @property Carbon|null $generated_at
 */
class PostDrawReport extends Model
{
    /** @use HasFactory<PostDrawReportFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'report_number', 'status', 'generated_at', 'generated_by', 'downloaded_at', 'downloaded_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'downloaded_at' => 'datetime',
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

    /** @return BelongsTo<User, $this> */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
