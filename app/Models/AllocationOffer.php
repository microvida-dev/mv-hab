<?php

namespace App\Models;

use App\Enums\AllocationOfferStatus;
use Database\Factories\AllocationOfferFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AllocationOffer extends Model
{
    /** @use HasFactory<AllocationOfferFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'offer_number', 'status', 'issued_by', 'issued_at', 'response_deadline_at', 'accepted_at', 'refused_at', 'expired_at', 'withdrawn_at', 'cancelled_at', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => AllocationOfferStatus::class,
            'issued_at' => 'datetime',
            'response_deadline_at' => 'datetime',
            'accepted_at' => 'datetime',
            'refused_at' => 'datetime',
            'expired_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Allocation, $this> */
    public function allocation(): BelongsTo
    {
        return $this->belongsTo(Allocation::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<User, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<ContestHousingUnit, $this> */
    public function contestHousingUnit(): BelongsTo
    {
        return $this->belongsTo(ContestHousingUnit::class);
    }

    /** @return BelongsTo<HousingUnit, $this> */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /** @return BelongsTo<User, $this> */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * @param  Builder<AllocationOffer>  $query
     * @return Builder<AllocationOffer>
     */
    public function scopePendingResponse(Builder $query): Builder
    {
        return $query->where('status', AllocationOfferStatus::PendingResponse->value);
    }
}
