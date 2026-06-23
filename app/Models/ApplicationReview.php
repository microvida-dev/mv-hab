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

/**
 * @property ApplicationReviewResult|null $result
 * @property ApplicationReviewStatus $status
 * @property ApplicationReviewType $review_type
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
     * @return HasMany<ApplicationReviewItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ApplicationReviewItem::class);
    }
}
