<?php

namespace App\Models;

use App\Enums\MaintenanceAssignmentStatus;
use App\Enums\MaintenanceAssignmentType;
use Database\Factories\MaintenanceAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceAssignment extends Model
{
    /** @use HasFactory<MaintenanceAssignmentFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'assignment_type' => MaintenanceAssignmentType::class,
            'status' => MaintenanceAssignmentStatus::class,
            'assigned_at' => 'datetime',
            'accepted_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * @return BelongsTo<MaintenanceSupplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSupplier::class, 'maintenance_supplier_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
