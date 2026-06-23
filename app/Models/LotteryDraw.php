<?php

namespace App\Models;

use App\Enums\LotteryDrawStatus;
use App\Enums\LotteryDrawType;
use Database\Factories\LotteryDrawFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $allocation_run_id
 * @property int|null $program_id
 * @property int|null $contest_id
 * @property int|null $definitive_list_id
 * @property LotteryDrawStatus $status
 * @property LotteryDrawType|null $draw_type
 * @property string|null $seed
 * @property string|null $algorithm
 * @property string|null $participants_hash
 * @property Carbon|null $participants_locked_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $validated_at
 * @property Carbon|null $cancelled_at
 * @property-read AllocationRun|null $allocationRun
 * @property-read DefinitiveList|null $definitiveList
 * @property-read Collection<int, LotteryParticipant> $participants
 * @property-read Collection<int, LotteryResult> $results
 */
class LotteryDraw extends Model
{
    /** @use HasFactory<LotteryDrawFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'lottery_runs';

    protected $guarded = [
        'id',
        'status',
        'started_by',
        'started_at',
        'completed_at',
        'validated_by',
        'validated_at',
        'failed_at',
        'failure_reason',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'locked_at',
        'locked_by',
        'participants_locked_at',
        'participants_locked_by',
        'audit_hash',
        'audit_payload',
        'participants_hash',
        'seed_hash',
        'result_hash',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LotteryDrawStatus::class,
            'draw_type' => LotteryDrawType::class,
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'validated_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'locked_at' => 'datetime',
            'participants_locked_at' => 'datetime',
            'audit_payload' => 'array',
        ];
    }

    /** @return BelongsTo<AllocationRun, $this> */
    public function allocationRun(): BelongsTo
    {
        return $this->belongsTo(AllocationRun::class);
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return BelongsTo<Contest, $this> */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /** @return BelongsTo<DefinitiveList, $this> */
    public function definitiveList(): BelongsTo
    {
        return $this->belongsTo(DefinitiveList::class);
    }

    /** @return BelongsTo<User, $this> */
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /** @return BelongsTo<User, $this> */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /** @return BelongsTo<User, $this> */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** @return BelongsTo<User, $this> */
    public function participantsLockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participants_locked_by');
    }

    /** @return HasMany<LotteryParticipant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(LotteryParticipant::class, 'lottery_run_id');
    }

    /** @return HasMany<LotteryResult, $this> */
    public function results(): HasMany
    {
        return $this->hasMany(LotteryResult::class, 'lottery_run_id')->orderBy('draw_order');
    }

    /** @return HasMany<LotteryDrawResult, $this> */
    public function drawResults(): HasMany
    {
        return $this->hasMany(LotteryDrawResult::class, 'lottery_run_id')->orderBy('draw_order');
    }

    /** @return HasMany<DrawConvocation, $this> */
    public function convocations(): HasMany
    {
        return $this->hasMany(DrawConvocation::class, 'lottery_run_id');
    }

    /** @return HasMany<DrawAttendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(DrawAttendance::class, 'lottery_run_id');
    }

    /** @return HasMany<WinnerRegistration, $this> */
    public function winnerRegistrations(): HasMany
    {
        return $this->hasMany(WinnerRegistration::class, 'lottery_run_id');
    }

    /** @return HasMany<RankingUpdateRun, $this> */
    public function rankingUpdateRuns(): HasMany
    {
        return $this->hasMany(RankingUpdateRun::class, 'lottery_run_id');
    }

    /** @return HasMany<PostDrawReport, $this> */
    public function postDrawReports(): HasMany
    {
        return $this->hasMany(PostDrawReport::class, 'lottery_run_id');
    }

    /** @return HasManyThrough<KeyHandoverAppointment, WinnerRegistration, $this> */
    public function keyHandoverAppointments(): HasManyThrough
    {
        return $this->hasManyThrough(
            KeyHandoverAppointment::class,
            WinnerRegistration::class,
            'lottery_run_id',
            'winner_registration_id'
        );
    }
}
