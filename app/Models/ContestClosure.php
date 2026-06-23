<?php

namespace App\Models;

use App\Enums\ContestClosureStatus;
use Database\Factories\ContestClosureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $contest_id
 * @property string $closure_number
 * @property ContestClosureStatus $status
 * @property Carbon|null $closed_at
 */
class ContestClosure extends Model
{
    /** @use HasFactory<ContestClosureFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'closure_number', 'status', 'closed_at', 'closed_by', 'archived_at', 'archived_by', 'cancelled_at', 'cancelled_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => ContestClosureStatus::class,
            'summary' => 'array',
            'critical_pending_items' => 'array',
            'snapshot' => 'array',
            'closed_at' => 'datetime',
            'archived_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Contest, $this> */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
