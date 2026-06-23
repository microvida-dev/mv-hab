<?php

namespace App\Models;

use Database\Factories\MaintenanceSupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceSupplier extends Model
{
    /** @use HasFactory<MaintenanceSupplierFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    /**
     * @return HasMany<MaintenanceAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(MaintenanceAssignment::class);
    }

    /**
     * @return HasMany<MaintenanceIntervention, $this>
     */
    public function interventions(): HasMany
    {
        return $this->hasMany(MaintenanceIntervention::class);
    }

    /**
     * @return HasMany<MaintenanceCost, $this>
     */
    public function costs(): HasMany
    {
        return $this->hasMany(MaintenanceCost::class);
    }
}
