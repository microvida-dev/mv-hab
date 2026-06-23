<?php

namespace App\Models;

use App\Enums\TechnicalHistoryEventType;
use Database\Factories\PropertyHistoryEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyHistoryEvent extends Model
{
    /** @use HasFactory<PropertyHistoryEventFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'event_type' => TechnicalHistoryEventType::class,
            'occurred_at' => 'datetime',
            'visible_to_tenant' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<HousingUnit, $this>
     */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'lease_contract_id');
    }

    /**
     * @return BelongsTo<MaintenanceRequest, $this>
     */
    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    /**
     * @return BelongsTo<PropertyInspection, $this>
     */
    public function propertyInspection(): BelongsTo
    {
        return $this->belongsTo(PropertyInspection::class);
    }
}
