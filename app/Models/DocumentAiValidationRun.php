<?php

namespace App\Models;

use App\Enums\DocumentAiValidationStatus;
use Database\Factories\DocumentAiValidationRunFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $application_id
 * @property DocumentAiValidationStatus $status
 * @property bool $requires_manual_review
 * @property-read Application $application
 */
class DocumentAiValidationRun extends Model
{
    /** @use HasFactory<DocumentAiValidationRunFactory> */
    use HasFactory;

    protected $fillable = [
        'status',
        'failure_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DocumentAiValidationStatus::class,
            'total_checks' => 'integer',
            'matches_count' => 'integer',
            'critical_count' => 'integer',
            'medium_count' => 'integer',
            'light_count' => 'integer',
            'inconclusive_count' => 'integer',
            'requires_manual_review' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<DocumentAiValidation, $this> */
    public function validations(): HasMany
    {
        return $this->hasMany(DocumentAiValidation::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeRequiresReview(Builder $query): Builder
    {
        return $query->where('requires_manual_review', true);
    }
}
