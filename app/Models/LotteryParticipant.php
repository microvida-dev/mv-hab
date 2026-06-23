<?php

namespace App\Models;

use App\Enums\LotteryParticipantStatus;
use Database\Factories\LotteryParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $lottery_run_id
 * @property int $application_id
 * @property int $user_id
 * @property int|null $definitive_list_entry_id
 * @property string $participant_number
 * @property int|null $rank_position
 * @property string|null $previous_score
 * @property LotteryParticipantStatus $status
 * @property bool $is_eligible
 */
class LotteryParticipant extends Model
{
    /** @use HasFactory<LotteryParticipantFactory> */
    use HasFactory;

    protected $guarded = ['id', 'participant_number', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'is_eligible' => 'boolean',
            'status' => LotteryParticipantStatus::class,
            'previous_score' => 'decimal:2',
            'snapshot' => 'array',
            'included_at' => 'datetime',
            'excluded_at' => 'datetime',
            'notified_at' => 'datetime',
            'present_at' => 'datetime',
            'absent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<LotteryRun, $this> */
    public function lotteryRun(): BelongsTo
    {
        return $this->belongsTo(LotteryRun::class);
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

    /** @return BelongsTo<DefinitiveListEntry, $this> */
    public function definitiveListEntry(): BelongsTo
    {
        return $this->belongsTo(DefinitiveListEntry::class);
    }

    /** @return HasMany<LotteryDrawResult, $this> */
    public function drawResults(): HasMany
    {
        return $this->hasMany(LotteryDrawResult::class);
    }

    /** @return HasMany<DrawConvocation, $this> */
    public function convocation(): HasMany
    {
        return $this->hasMany(DrawConvocation::class);
    }

    /** @return HasMany<DrawAttendance, $this> */
    public function attendance(): HasMany
    {
        return $this->hasMany(DrawAttendance::class);
    }
}
