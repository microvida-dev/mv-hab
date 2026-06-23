<?php

namespace App\Models;

use App\Enums\MaintenanceCostStatus;
use App\Enums\MaintenanceCostType;
use Database\Factories\MaintenanceCostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceCost extends Model
{
    /** @use HasFactory<MaintenanceCostFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'approved_by', 'approved_at', 'rejection_reason'];

    protected function casts(): array
    {
        return [
            'cost_type' => MaintenanceCostType::class,
            'status' => MaintenanceCostStatus::class,
            'amount' => 'decimal:2',
            'registered_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<MaintenanceRequest, $this> */
    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    /** @return BelongsTo<MaintenanceIntervention, $this> */
    public function intervention(): BelongsTo
    {
        return $this->belongsTo(MaintenanceIntervention::class, 'maintenance_intervention_id');
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

    /** @return BelongsTo<MaintenanceSupplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSupplier::class, 'maintenance_supplier_id');
    }
}
