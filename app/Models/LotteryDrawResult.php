<?php

namespace App\Models;

use App\Enums\LotteryResultStatus;
use App\Enums\LotteryResultType;
use Database\Factories\LotteryDrawResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LotteryDrawResult extends Model
{
    /** @use HasFactory<LotteryDrawResultFactory> */
    use HasFactory;

    protected $guarded = ['id', 'draw_order', 'result_type', 'random_value', 'audit_data', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'result_type' => LotteryResultType::class,
            'status' => LotteryResultStatus::class,
            'selected' => 'boolean',
            'audit_data' => 'array',
            'validated_at' => 'datetime',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<LotteryRun, $this>
     */
    public function lotteryRun(): BelongsTo
    {
        return $this->belongsTo(LotteryRun::class);
    }

    /**
     * @return BelongsTo<LotteryParticipant, $this>
     */
    public function lotteryParticipant(): BelongsTo
    {
        return $this->belongsTo(LotteryParticipant::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<ContestHousingUnit, $this>
     */
    public function assignedContestHousingUnit(): BelongsTo
    {
        return $this->belongsTo(ContestHousingUnit::class, 'assigned_contest_housing_unit_id');
    }

    /**
     * @return BelongsTo<HousingUnit, $this>
     */
    public function assignedHousingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class, 'assigned_housing_unit_id');
    }

    /**
     * @return HasOne<WinnerRegistration, $this>
     */
    public function winnerRegistration(): HasOne
    {
        return $this->hasOne(WinnerRegistration::class, 'lottery_draw_result_id');
    }
}
