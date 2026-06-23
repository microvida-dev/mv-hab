<?php

namespace App\Models;

use App\Enums\MaintenanceRequestStatus;
use Database\Factories\MaintenanceRequestStatusHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRequestStatusHistory extends Model
{
    /** @use HasFactory<MaintenanceRequestStatusHistoryFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'from_status' => MaintenanceRequestStatus::class,
            'to_status' => MaintenanceRequestStatus::class,
            'changed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<MaintenanceRequest, $this>
     */
    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
