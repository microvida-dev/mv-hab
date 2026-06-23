<?php

namespace App\Models;

use App\Enums\LotteryDrawType;
use App\Enums\LotteryRunStatus;
use Database\Factories\LotteryRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LotteryRun extends Model
{
    /** @use HasFactory<LotteryRunFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'started_by', 'started_at', 'completed_at', 'failed_at', 'failure_reason', 'locked_at', 'locked_by', 'audit_hash', 'audit_payload', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => LotteryRunStatus::class,
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

    /**
     * @return BelongsTo<AllocationRun, $this>
     */
    public function allocationRun(): BelongsTo
    {
        return $this->belongsTo(AllocationRun::class);
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<Contest, $this>
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /**
     * @return BelongsTo<DefinitiveList, $this>
     */
    public function definitiveList(): BelongsTo
    {
        return $this->belongsTo(DefinitiveList::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * @return HasMany<LotteryParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(LotteryParticipant::class);
    }

    /**
     * @return HasMany<LotteryDrawResult, $this>
     */
    public function drawResults(): HasMany
    {
        return $this->hasMany(LotteryDrawResult::class)->orderBy('draw_order');
    }

    /**
     * @return HasMany<DrawConvocation, $this>
     */
    public function convocations(): HasMany
    {
        return $this->hasMany(DrawConvocation::class);
    }

    /**
     * @return HasMany<DrawAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(DrawAttendance::class);
    }

    /**
     * @return HasMany<WinnerRegistration, $this>
     */
    public function winnerRegistrations(): HasMany
    {
        return $this->hasMany(WinnerRegistration::class);
    }

    /**
     * @return HasMany<PostDrawReport, $this>
     */
    public function postDrawReports(): HasMany
    {
        return $this->hasMany(PostDrawReport::class);
    }
}
