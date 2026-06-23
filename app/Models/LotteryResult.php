<?php

namespace App\Models;

use App\Enums\LotteryResultStatus;
use App\Enums\LotteryResultType;
use Database\Factories\LotteryResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $lottery_run_id
 * @property int|null $lottery_participant_id
 * @property int $application_id
 * @property int $user_id
 * @property bool $selected
 * @property int|null $assigned_contest_housing_unit_id
 * @property int|null $assigned_housing_unit_id
 * @property int $draw_order
 * @property LotteryResultType $result_type
 * @property LotteryResultStatus $status
 * @property-read LotteryDraw $lotteryDraw
 * @property-read LotteryParticipant|null $lotteryParticipant
 */
class LotteryResult extends Model
{
    /** @use HasFactory<LotteryResultFactory> */
    use HasFactory;

    protected $table = 'lottery_draw_results';

    protected $guarded = [
        'id',
        'draw_order',
        'result_type',
        'status',
        'random_value',
        'result_hash',
        'audit_data',
        'validated_by',
        'validated_at',
        'approved_by',
        'approved_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'created_at',
        'updated_at',
    ];

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

    /** @return BelongsTo<LotteryDraw, $this> */
    public function lotteryDraw(): BelongsTo
    {
        return $this->belongsTo(LotteryDraw::class, 'lottery_run_id');
    }

    /** @return BelongsTo<LotteryParticipant, $this> */
    public function lotteryParticipant(): BelongsTo
    {
        return $this->belongsTo(LotteryParticipant::class);
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
    public function assignedContestHousingUnit(): BelongsTo
    {
        return $this->belongsTo(ContestHousingUnit::class, 'assigned_contest_housing_unit_id');
    }

    /** @return BelongsTo<HousingUnit, $this> */
    public function assignedHousingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class, 'assigned_housing_unit_id');
    }

    /** @return BelongsTo<User, $this> */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
