<?php

namespace App\Models;

use App\Enums\CorrectionRequestStatus;
use Database\Factories\CorrectionRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property bool $candidate_visible
 * @property CorrectionRequestStatus $status
 * @property Carbon|null $response_deadline_at
 */
class CorrectionRequest extends Model
{
    /** @use HasFactory<CorrectionRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'id',
        'administrative_process_id',
        'application_id',
        'user_id',
        'request_number',
        'status',
        'issued_by',
        'issued_at',
        'responded_at',
        'closed_at',
        'cancelled_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CorrectionRequestStatus::class,
            'issued_at' => 'datetime',
            'response_deadline_at' => 'datetime',
            'responded_at' => 'datetime',
            'closed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'candidate_visible' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'request_number';
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
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * @return HasMany<CorrectionRequestItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CorrectionRequestItem::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<CorrectionResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(CorrectionResponse::class);
    }

    public function isOpenForCandidateResponse(): bool
    {
        return $this->candidate_visible
            && in_array($this->status, [
                CorrectionRequestStatus::Issued,
                CorrectionRequestStatus::Open,
                CorrectionRequestStatus::PartiallyResponded,
            ], true)
            && ($this->response_deadline_at === null || $this->response_deadline_at->isFuture());
    }
}
