<?php

namespace App\Models;

use App\Enums\AdministrativeProcessStatus;
use Database\Factories\AdministrativeProcessFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdministrativeProcess extends Model
{
    /** @use HasFactory<AdministrativeProcessFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'id',
        'process_number',
        'application_id',
        'user_id',
        'status',
        'created_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AdministrativeProcessStatus::class,
            'assigned_at' => 'datetime',
            'received_at' => 'datetime',
            'preliminary_review_started_at' => 'datetime',
            'document_review_started_at' => 'datetime',
            'eligibility_review_started_at' => 'datetime',
            'admitted_for_scoring_at' => 'datetime',
            'not_admitted_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'process_number';
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
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
     * @return BelongsTo<User, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return BelongsTo<CorrectionRequest, $this>
     */
    public function currentCorrectionRequest(): BelongsTo
    {
        return $this->belongsTo(CorrectionRequest::class, 'current_correction_request_id');
    }

    /**
     * @return HasMany<AdministrativeProcessStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(AdministrativeProcessStatusHistory::class)->latest('created_at');
    }

    /**
     * @return HasMany<ApplicationReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ApplicationReview::class);
    }

    /**
     * @return HasMany<CorrectionRequest, $this>
     */
    public function correctionRequests(): HasMany
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    /**
     * @return HasMany<CorrectionResponse, $this>
     */
    public function correctionResponses(): HasMany
    {
        return $this->hasMany(CorrectionResponse::class);
    }

    /**
     * @return HasMany<AdministrativeDecision, $this>
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(AdministrativeDecision::class);
    }

    /**
     * @return HasMany<AdministrativeTask, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(AdministrativeTask::class);
    }

    /**
     * @return HasMany<AdministrativeProcessNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(AdministrativeProcessNote::class);
    }

    /**
     * @param  Builder<AdministrativeProcess>  $query
     * @return Builder<AdministrativeProcess>
     */
    public function scopeAdmittedForScoring(Builder $query): Builder
    {
        return $query->where('status', AdministrativeProcessStatus::AdmittedForScoring->value);
    }

    public function isClosed(): bool
    {
        $rawStatus = $this->getAttribute('status');

        $status = $rawStatus instanceof AdministrativeProcessStatus
            ? $rawStatus
            : AdministrativeProcessStatus::from((string) $rawStatus);

        return $status->isFinal();
    }
}
