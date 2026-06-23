<?php

namespace App\Models;

use Database\Factories\LandlordDashboardSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandlordDashboardSnapshot extends Model
{
    /** @use HasFactory<LandlordDashboardSnapshotFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'generated_at' => 'datetime',
            'monthly_billed' => 'decimal:2',
            'monthly_collected' => 'decimal:2',
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
