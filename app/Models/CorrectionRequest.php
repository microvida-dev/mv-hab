<?php

namespace App\Models;

use App\Casts\CorrectionRequestStatusCast;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionRevalidationAggregateResult;
use Database\Factories\CorrectionRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $application_review_publication_result_id
 * @property string|null $source_snapshot_hash
 * @property int $administrative_process_id
 * @property int $application_id
 * @property int $user_id
 * @property int|null $issued_by
 * @property string $request_number
 * @property bool $candidate_visible
 * @property string $subject
 * @property CorrectionRequestStatus $status
 * @property Carbon|null $issued_at
 * @property Carbon|null $notified_at
 * @property Carbon|null $opened_at
 * @property Carbon|null $response_deadline_at
 * @property Carbon|null $original_response_deadline_at
 * @property int $deadline_extension_count
 * @property Carbon|null $responded_at
 * @property Carbon|null $submitted_at
 * @property int|null $revalidation_started_by
 * @property Carbon|null $revalidation_started_at
 * @property CorrectionRevalidationAggregateResult|null $revalidation_result
 * @property int|null $revalidation_publication_result_id
 * @property int|null $revalidation_projected_by
 * @property Carbon|null $revalidation_projected_at
 * @property Carbon|null $expired_at
 * @property Carbon|null $resolved_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read ApplicationReviewPublicationResult|null $publicationResult
 * @property-read AdministrativeProcess $administrativeProcess
 * @property-read Application $application
 * @property-read User $candidate
 */
class CorrectionRequest extends Model
{
    /** @use HasFactory<CorrectionRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'id',
        'application_review_publication_result_id',
        'source_snapshot_hash',
        'administrative_process_id',
        'application_id',
        'user_id',
        'request_number',
        'status',
        'issued_by',
        'issued_at',
        'notified_at',
        'opened_at',
        'original_response_deadline_at',
        'deadline_extension_count',
        'responded_at',
        'submitted_at',
        'revalidation_started_by',
        'revalidation_started_at',
        'revalidation_result',
        'revalidation_publication_result_id',
        'revalidation_projected_by',
        'revalidation_projected_at',
        'expired_at',
        'resolved_at',
        'closed_at',
        'cancelled_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CorrectionRequestStatusCast::class,
            'issued_at' => 'datetime',
            'notified_at' => 'datetime',
            'opened_at' => 'datetime',
            'response_deadline_at' => 'datetime',
            'original_response_deadline_at' => 'datetime',
            'deadline_extension_count' => 'integer',
            'responded_at' => 'datetime',
            'submitted_at' => 'datetime',
            'revalidation_started_at' => 'datetime',
            'revalidation_result' => CorrectionRevalidationAggregateResult::class,
            'revalidation_projected_at' => 'datetime',
            'expired_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'candidate_visible' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'request_number';
    }

    /** @return BelongsTo<ApplicationReviewPublicationResult, $this> */
    public function publicationResult(): BelongsTo
    {
        return $this->belongsTo(
            ApplicationReviewPublicationResult::class,
            'application_review_publication_result_id',
        );
    }

    /** @return BelongsTo<AdministrativeProcess, $this> */
    public function administrativeProcess(): BelongsTo
    {
        return $this->belongsTo(AdministrativeProcess::class);
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

    /** @return BelongsTo<User, $this> */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /** @return HasMany<CorrectionRequestItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CorrectionRequestItem::class)->orderBy('sort_order');
    }

    /** @return HasMany<CorrectionResponse, $this> */
    public function responses(): HasMany
    {
        return $this->hasMany(CorrectionResponse::class);
    }

    /** @return HasOne<CorrectionSubmissionReceipt, $this> */
    public function submissionReceipt(): HasOne
    {
        return $this->hasOne(CorrectionSubmissionReceipt::class);
    }

    /** @return HasOne<ApplicationReviewBatch, $this> */
    public function revalidationBatch(): HasOne
    {
        return $this->hasOne(
            ApplicationReviewBatch::class,
            'correction_request_id',
        );
    }

    /** @return BelongsTo<ApplicationReviewPublicationResult, $this> */
    public function revalidationPublicationResult(): BelongsTo
    {
        return $this->belongsTo(
            ApplicationReviewPublicationResult::class,
            'revalidation_publication_result_id',
        );
    }

    /** @return BelongsTo<User, $this> */
    public function revalidationStartedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revalidation_started_by');
    }

    /** @return BelongsTo<User, $this> */
    public function revalidationProjectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revalidation_projected_by');
    }

    /** @return HasMany<CorrectionDeadlineExtension, $this> */
    public function deadlineExtensions(): HasMany
    {
        return $this->hasMany(
            CorrectionDeadlineExtension::class,
        )->orderBy('authorized_at');
    }

    public function isLegacy(): bool
    {
        return $this->application_review_publication_result_id === null;
    }

    public function effectiveDeadline(): ?Carbon
    {
        return $this->response_deadline_at;
    }

    public function hasAuthoritativeLegacyOrigin(): bool
    {
        if (
            ! $this->isLegacy()
            || ! $this->candidate_visible
            || $this->issued_at === null
            || $this->notified_at === null
            || $this->opened_at === null
        ) {
            return false;
        }

        $application = $this->getRelationValue('application');
        $process = $this->getRelationValue('administrativeProcess');

        if (
            ! $application instanceof Application
            || ! $process instanceof AdministrativeProcess
        ) {
            return false;
        }

        return (int) $application->id === (int) $this->application_id
            && (int) $application->user_id === (int) $this->user_id
            && (int) $process->id === (int) $this->administrative_process_id
            && (int) $process->application_id === (int) $this->application_id
            && (int) $process->user_id === (int) $this->user_id;
    }

    public function isVisibleToCandidate(?Carbon $at = null): bool
    {
        if (! $this->candidate_visible || $this->status === CorrectionRequestStatus::Cancelled) {
            return false;
        }

        $reference = $at ?? now();

        if ($this->isLegacy()) {
            return $this->hasAuthoritativeLegacyOrigin()
                && $this->issued_at?->lessThanOrEqualTo($reference) === true
                && $this->notified_at?->lessThanOrEqualTo($reference) === true;
        }

        $publishedAt = $this->publicationResult?->published_at;

        return $publishedAt !== null && $publishedAt->lessThanOrEqualTo($reference);
    }

    public function isResponseWindowOpen(?Carbon $at = null): bool
    {
        $reference = $at ?? now();

        return $this->isVisibleToCandidate($reference)
            && $this->status->acceptsCandidateWork()
            && ($this->opened_at === null || $reference->greaterThanOrEqualTo($this->opened_at))
            && ($this->response_deadline_at === null || $reference->lessThanOrEqualTo($this->response_deadline_at));
    }

    public function isOpenForCandidateResponse(): bool
    {
        return $this->isResponseWindowOpen();
    }
}
