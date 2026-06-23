<?php

namespace App\Models;

use Database\Factories\WinnerRegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $lottery_run_id
 * @property int $lottery_draw_result_id
 * @property int|null $allocation_id
 * @property int $application_id
 * @property int $user_id
 * @property int|null $contest_housing_unit_id
 * @property int|null $housing_unit_id
 * @property string $status
 * @property Carbon|null $registered_at
 * @property-read Allocation|null $allocation
 * @property-read LotteryDraw $lotteryDraw
 * @property-read LotteryResult $lotteryResult
 * @property-read KeyHandoverAppointment|null $latestKeyHandoverAppointment
 */
class WinnerRegistration extends Model
{
    /** @use HasFactory<WinnerRegistrationFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'registered_at', 'registered_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<LotteryDraw, $this> */
    public function lotteryDraw(): BelongsTo
    {
        return $this->belongsTo(LotteryDraw::class, 'lottery_run_id');
    }

    /** @return BelongsTo<LotteryResult, $this> */
    public function lotteryResult(): BelongsTo
    {
        return $this->belongsTo(LotteryResult::class, 'lottery_draw_result_id');
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

    /** @return HasMany<KeyHandoverAppointment, $this> */
    public function keyHandoverAppointments(): HasMany
    {
        return $this->hasMany(KeyHandoverAppointment::class);
    }

    /** @return HasOne<KeyHandoverAppointment, $this> */
    public function latestKeyHandoverAppointment(): HasOne
    {
        return $this->hasOne(KeyHandoverAppointment::class)->latestOfMany();
    }
}
