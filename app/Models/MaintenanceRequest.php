<?php

namespace App\Models;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceRequestStatus;
use App\Enums\MaintenanceSource;
use App\Enums\MaintenanceUrgency;
use Database\Factories\MaintenanceRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $reported_at
 * @property Carbon|null $resolved_at
 */
class MaintenanceRequest extends Model
{
    /** @use HasFactory<MaintenanceRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'housing_unit_id',
        'citizen_id',
        'lease_contract_id',
        'application_id',
        'user_id',
        'maintenance_category_id',
        'request_number',
        'source',
        'title',
        'description',
        'location_in_property',
        'tenant_availability',
        'access_instructions',
        'priority',
        'urgency',
        'technical_priority',
        'status',
        'reported_at',
        'scheduled_for',
        'resolved_at',
        'review_notes',
        'resolution_summary',
        'rejection_reason',
        'closure_notes',
        'reviewed_at',
        'reviewed_by',
        'closed_at',
        'closed_by',
        'cancelled_at',
        'cancelled_by',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'resolved_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'closed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'priority' => MaintenancePriority::class,
            'urgency' => MaintenanceUrgency::class,
            'technical_priority' => MaintenanceUrgency::class,
            'status' => MaintenanceRequestStatus::class,
            'source' => MaintenanceSource::class,
        ];
    }

    /** @return BelongsTo<HousingUnit, $this> */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /** @return BelongsTo<Citizen, $this> */
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    /** @return BelongsTo<Contract, $this> */
    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'lease_contract_id');
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<MaintenanceCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCategory::class, 'maintenance_category_id');
    }

    /** @return HasMany<MaintenanceRequestStatusHistory, $this> */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(MaintenanceRequestStatusHistory::class)->latest('changed_at');
    }

    /** @return HasMany<MaintenanceAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(MaintenanceAssignment::class);
    }

    /** @return HasMany<MaintenanceIntervention, $this> */
    public function interventions(): HasMany
    {
        return $this->hasMany(MaintenanceIntervention::class);
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

    /** @return HasMany<PropertyHistoryEvent, $this> */
    public function propertyHistoryEvents(): HasMany
    {
        return $this->hasMany(PropertyHistoryEvent::class);
    }
}
