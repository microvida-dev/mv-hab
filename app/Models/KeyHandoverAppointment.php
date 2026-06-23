<?php

namespace App\Models;

use App\Enums\KeyHandoverStatus;
use Database\Factories\KeyHandoverAppointmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $winner_registration_id
 * @property int|null $allocation_id
 * @property int $application_id
 * @property int $user_id
 * @property int $contest_id
 * @property int|null $housing_unit_id
 * @property KeyHandoverStatus $status
 * @property Carbon|null $scheduled_for
 * @property Carbon|null $completed_at
 * @property-read WinnerRegistration $winnerRegistration
 */
class KeyHandoverAppointment extends Model
{
    /** @use HasFactory<KeyHandoverAppointmentFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'cancelled_at', 'cancelled_by', 'completed_at', 'completed_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => KeyHandoverStatus::class,
            'scheduled_for' => 'datetime',
            'rescheduled_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<WinnerRegistration, $this> */
    public function winnerRegistration(): BelongsTo
    {
        return $this->belongsTo(WinnerRegistration::class);
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

    /** @return BelongsTo<Contest, $this> */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /** @return BelongsTo<HousingUnit, $this> */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }
}
