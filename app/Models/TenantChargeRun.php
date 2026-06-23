<?php

namespace App\Models;

use App\Enums\ChargeRunStatus;
use App\Enums\ChargeType;
use Database\Factories\TenantChargeRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ChargeRunStatus $status
 */
class TenantChargeRun extends Model
{
    /** @use HasFactory<TenantChargeRunFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'run_number', 'status', 'generated_count', 'skipped_count', 'warning_count', 'total_amount', 'started_at', 'completed_at', 'cancelled_at', 'created_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => ChargeRunStatus::class,
            'charge_type' => ChargeType::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'total_amount' => 'decimal:2',
            'warnings' => 'array',
        ];
    }

    /**
     * @return HasMany<TenantChargeRunItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TenantChargeRunItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
