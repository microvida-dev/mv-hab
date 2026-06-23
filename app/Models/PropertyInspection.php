<?php

namespace App\Models;

use App\Enums\InspectionCondition;
use App\Enums\InspectionStatus;
use App\Enums\InspectionType;
use Database\Factories\PropertyInspectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyInspection extends Model
{
    /** @use HasFactory<PropertyInspectionFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'inspection_number', 'status', 'validated_by', 'validated_at'];

    protected function casts(): array
    {
        return [
            'inspection_type' => InspectionType::class,
            'status' => InspectionStatus::class,
            'scheduled_for' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'validated_at' => 'datetime',
            'general_condition' => InspectionCondition::class,
            'tenant_visible' => 'boolean',
            'tenant_present' => 'boolean',
        ];
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

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<InspectionChecklistTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(InspectionChecklistTemplate::class, 'inspection_checklist_template_id');
    }

    /** @return BelongsTo<User, $this> */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_user_id');
    }

    /** @return HasMany<PropertyInspectionItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PropertyInspectionItem::class)->orderBy('sort_order');
    }

    /** @return HasMany<PropertyInspectionAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(PropertyInspectionAttachment::class);
    }

    /** @return HasOne<PropertyInspectionReport, $this> */
    public function report(): HasOne
    {
        return $this->hasOne(PropertyInspectionReport::class);
    }
}
