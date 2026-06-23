<?php

namespace App\Models;

use App\Enums\RentManualReviewStatus;
use Database\Factories\RentManualReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentManualReview extends Model
{
    /** @use HasFactory<RentManualReviewFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'reviewed_by', 'approved_rent', 'reviewed_at', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => RentManualReviewStatus::class,
            'original_rent' => 'decimal:2',
            'proposed_rent' => 'decimal:2',
            'approved_rent' => 'decimal:2',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<RentCalculation, $this>
     */
    public function rentCalculation(): BelongsTo
    {
        return $this->belongsTo(RentCalculation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
