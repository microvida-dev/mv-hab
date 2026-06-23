<?php

namespace App\Models;

use App\Enums\MaintenanceInterventionStatus;
use Database\Factories\MaintenanceInterventionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceIntervention extends Model
{
    /** @use HasFactory<MaintenanceInterventionFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => MaintenanceInterventionStatus::class,
            'scheduled_for' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'requires_follow_up' => 'boolean',
            'follow_up_date' => 'date',
        ];
    }

    /** @return BelongsTo<MaintenanceRequest, $this> */
    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    /** @return BelongsTo<HousingUnit, $this> */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /** @return BelongsTo<Contract, $this> */
    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'lease_contract_id');
    }

    /** @return BelongsTo<User, $this> */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    /** @return BelongsTo<MaintenanceSupplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSupplier::class, 'maintenance_supplier_id');
    }

    /** @return HasMany<MaintenanceAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(MaintenanceAttachment::class);
    }

    /** @return HasMany<MaintenanceCost, $this> */
    public function costs(): HasMany
    {
        return $this->hasMany(MaintenanceCost::class);
    }
}
