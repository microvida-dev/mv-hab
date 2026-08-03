<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PublicVisitBookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property PublicVisitBookingStatus $status
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $cancellation_token
 * @property Carbon|null $privacy_notice_accepted_at
 * @property Carbon|null $booked_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $cancellation_token_expires_at
 * @property Carbon|null $retention_due_at
 * @property Carbon|null $anonymized_at
 */
class PublicVisitBooking extends Model
{
    protected $guarded = [
        'id',
        'municipality_id',
        'visit_slot_id',
        'housing_unit_id',
        'contest_id',
        'status',
        'email_hash',
        'active_fingerprint',
        'cancellation_token_hash',
        'booked_at',
        'cancelled_at',
        'confirmation_sent_at',
        'confirmation_failed_at',
        'confirmation_error_code',
        'retention_due_at',
        'anonymized_at',
        'status_changed_by',
        'created_at',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PublicVisitBookingStatus::class,
            'contact_name' => 'encrypted',
            'contact_email' => 'encrypted',
            'contact_phone' => 'encrypted',
            'cancellation_token' => 'encrypted',
            'status_notes' => 'encrypted',
            'privacy_notice_accepted_at' => 'datetime',
            'booked_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
            'confirmation_failed_at' => 'datetime',
            'cancellation_token_expires_at' => 'datetime',
            'retention_due_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(static fn (): bool => false);
    }

    /** @return BelongsTo<Municipality, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /** @return BelongsTo<VisitSlot, $this> */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(VisitSlot::class, 'visit_slot_id');
    }

    /** @return BelongsTo<HousingUnit, $this> */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /** @return BelongsTo<Contest, $this> */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', PublicVisitBookingStatus::Booked->value)
            ->whereNull('anonymized_at');
    }

    public function isActive(): bool
    {
        return $this->status === PublicVisitBookingStatus::Booked
            && $this->anonymized_at === null;
    }
}
