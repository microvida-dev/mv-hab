<?php

namespace App\Models;

use App\Enums\ApplicationReviewResult;
use App\Enums\ApplicationReviewStatus;
use App\Enums\ApplicationReviewType;
use Database\Factories\ApplicationReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $administrative_process_id
 * @property int $application_id
 * @property ApplicationReviewResult|null $result
 * @property ApplicationReviewStatus $status
 * @property ApplicationReviewType $review_type
 * @property int|null $reviewed_by
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $ready_for_closure_at
 * @property int|null $ready_for_closure_by
 * @property Carbon|null $last_activity_at
 * @property int $lock_version
 */
class ApplicationReview extends Model
{
    /** @use HasFactory<ApplicationReviewFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'review_type',
        'summary',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'review_type' => ApplicationReviewType::class,
            'status' => ApplicationReviewStatus::class,
            'result' => ApplicationReviewResult::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'ready_for_closure_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AdministrativeProcess, $this>
     */
    public function administrativeProcess(): BelongsTo
    {
        return $this->belongsTo(AdministrativeProcess::class);
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
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function readyForClosureBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ready_for_closure_by');
    }

    public function isReadyForClosure(): bool
    {
        return $this->status === ApplicationReviewStatus::ReadyForClosure;
    }

    /**
     * @return HasMany<ApplicationReviewItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ApplicationReviewItem::class);
    }
}
