<?php

namespace App\Models;

use App\Enums\HousingLocationPrecision;
use App\Enums\HousingPublicStatus;
use App\Enums\HousingUnitStatus;
use App\Enums\PublicVisibilityStatus;
use Database\Factories\HousingUnitFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HousingUnit extends Model
{
    /** @use HasFactory<HousingUnitFactory> */
    use HasFactory;

    protected $fillable = [
        'municipality_id',
        'code',
        'address',
        'typology',
        'bedrooms',
        'monthly_rent',
        'status',
        'public_reference',
        'public_title',
        'public_slug',
        'public_summary',
        'public_description',
        'parish',
        'locality',
        'postal_code',
        'floor',
        'gross_area_sqm',
        'usable_area_sqm',
        'energy_rating',
        'public_location_description',
        'public_address_visible',
        'public_latitude',
        'public_longitude',
        'public_location_precision',
        'public_status',
        'public_visibility_status',
        'is_public',
        'published_at',
        'unpublished_at',
        'public_sort_order',
        'seo_title',
        'seo_description',
        'og_image_path',
    ];

    protected function casts(): array
    {
        return [
            'monthly_rent' => 'decimal:2',
            'status' => HousingUnitStatus::class,
            'gross_area_sqm' => 'decimal:2',
            'usable_area_sqm' => 'decimal:2',
            'public_address_visible' => 'boolean',
            'public_latitude' => 'decimal:7',
            'public_longitude' => 'decimal:7',
            'public_location_precision' => HousingLocationPrecision::class,
            'public_status' => HousingPublicStatus::class,
            'public_visibility_status' => PublicVisibilityStatus::class,
            'is_public' => 'boolean',
            'published_at' => 'datetime',
            'unpublished_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Municipality, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /** @return HasMany<Contract, $this> */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /** @return HasMany<Contract, $this> */
    public function leaseContracts(): HasMany
    {
        return $this->hasMany(Contract::class)->processual();
    }

    /** @return HasOne<Contract, $this> */
    public function activeLeaseContract(): HasOne
    {
        return $this->hasOne(Contract::class)
            ->whereIn('status', ['preparation', 'issued', 'signed', 'active'])
            ->latestOfMany();
    }

    /** @return HasMany<ContestHousingUnit, $this> */
    public function contestHousingUnits(): HasMany
    {
        return $this->hasMany(ContestHousingUnit::class);
    }

    /** @return HasMany<HousingUnitFeature, $this> */
    public function features(): HasMany
    {
        return $this->hasMany(HousingUnitFeature::class)->orderBy('sort_order');
    }

    /** @return HasMany<HousingUnitFeature, $this> */
    public function publicFeatures(): HasMany
    {
        return $this->features()->where('is_public', true);
    }

    /** @return HasMany<HousingUnitImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(HousingUnitImage::class)->orderBy('sort_order');
    }

    /** @return HasMany<HousingUnitImage, $this> */
    public function publicImages(): HasMany
    {
        return $this->images()->publiclyVisible();
    }

    /** @return HasOne<HousingUnitImage, $this> */
    public function coverImage(): HasOne
    {
        return $this->hasOne(HousingUnitImage::class)
            ->where('is_public', true)
            ->where('is_cover', true)
            ->oldest('sort_order');
    }

    /** @return HasMany<HousingUnitPublicDocument, $this> */
    public function publicDocuments(): HasMany
    {
        return $this->hasMany(HousingUnitPublicDocument::class)
            ->publiclyVisible()
            ->orderBy('sort_order');
    }

    /** @return HasMany<HousingUnitPublicDocument, $this> */
    public function publicDocumentRecords(): HasMany
    {
        return $this->hasMany(HousingUnitPublicDocument::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<VisitAvailability, $this>
     */
    public function visitAvailabilities(): HasMany
    {
        return $this->hasMany(VisitAvailability::class);
    }

    /**
     * @return HasMany<VisitSlot, $this>
     */
    public function visitSlots(): HasMany
    {
        return $this->hasMany(VisitSlot::class);
    }

    /**
     * @return HasMany<HousingVisit, $this>
     */
    public function housingVisits(): HasMany
    {
        return $this->hasMany(HousingVisit::class);
    }

    /** @return HasMany<Allocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    /** @return HasMany<AllocationOffer, $this> */
    public function allocationOffers(): HasMany
    {
        return $this->hasMany(AllocationOffer::class);
    }

    /** @return HasMany<MaintenanceRequest, $this> */
    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    /** @return HasMany<TenantContractAccess, $this> */
    public function tenantContractAccesses(): HasMany
    {
        return $this->hasMany(TenantContractAccess::class);
    }

    /** @return HasMany<TenantInvoice, $this> */
    public function tenantInvoices(): HasMany
    {
        return $this->hasMany(TenantInvoice::class);
    }

    /** @return HasMany<TenantCommunication, $this> */
    public function tenantCommunications(): HasMany
    {
        return $this->hasMany(TenantCommunication::class);
    }

    /** @return HasMany<MaintenanceIntervention, $this> */
    public function maintenanceInterventions(): HasMany
    {
        return $this->hasMany(MaintenanceIntervention::class);
    }

    /** @return HasMany<MaintenanceCost, $this> */
    public function maintenanceCosts(): HasMany
    {
        return $this->hasMany(MaintenanceCost::class);
    }

    /** @return HasMany<PropertyInspection, $this> */
    public function propertyInspections(): HasMany
    {
        return $this->hasMany(PropertyInspection::class);
    }

    /** @return HasMany<PropertyHistoryEvent, $this> */
    public function propertyHistoryEvents(): HasMany
    {
        return $this->hasMany(PropertyHistoryEvent::class)->latest('occurred_at');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAllocatedForContract(Builder $query): Builder
    {
        return $query->whereHas('allocations', function (Builder $builder): void {
            /** @var Builder<Allocation> $builder */
            $builder->readyForContract();
        });
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('is_public', true)
            ->where('public_visibility_status', PublicVisibilityStatus::Published->value)
            ->where(function (Builder $builder): void {
                $builder->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->whereNull('unpublished_at');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublicOrder(Builder $query): Builder
    {
        return $query
            ->orderBy('public_sort_order')
            ->orderByDesc('published_at')
            ->orderBy('code');
    }

    public function displayTitle(): string
    {
        return $this->public_title ?: trim(($this->typology ?: 'Habitação').' '.$this->code);
    }

    public function publicLocationLabel(): string
    {
        return $this->public_location_description
            ?: collect([$this->parish, $this->locality])->filter()->join(', ')
            ?: 'Localização a confirmar';
    }

    public function publicAddressForDisplay(): ?string
    {
        if (! $this->public_address_visible) {
            return null;
        }

        return $this->address;
    }

    public function hasPublicCoordinates(): bool
    {
        return $this->public_latitude !== null && $this->public_longitude !== null;
    }
}
