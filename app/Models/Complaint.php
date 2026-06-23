<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use Database\Factories\ComplaintFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    /** @use HasFactory<ComplaintFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['subject', 'grounds', 'requested_outcome'];

    protected function casts(): array
    {
        return [
            'status' => ComplaintStatus::class,
            'submitted_at' => 'datetime',
            'received_at' => 'datetime',
            'review_started_at' => 'datetime',
            'review_completed_at' => 'datetime',
            'assigned_at' => 'datetime',
            'requires_additional_information' => 'boolean',
            'additional_information_requested_at' => 'datetime',
            'additional_information_deadline_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'closed_at' => 'datetime',
            'candidate_visible' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'complaint_number';
    }

    /**
     * @return BelongsTo<ProvisionalList, $this>
     */
    public function provisionalList(): BelongsTo
    {
        return $this->belongsTo(ProvisionalList::class);
    }

    /**
     * @return BelongsTo<ProvisionalListEntry, $this>
     */
    public function provisionalListEntry(): BelongsTo
    {
        return $this->belongsTo(ProvisionalListEntry::class);
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
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<ComplaintAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ComplaintAttachment::class);
    }

    /**
     * @return HasMany<ComplaintReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ComplaintReview::class);
    }

    /**
     * @return HasOne<ComplaintDecision, $this>
     */
    public function decision(): HasOne
    {
        return $this->hasOne(ComplaintDecision::class);
    }

    /**
     * @return HasMany<AdditionalInformationRequest, $this>
     */
    public function additionalInformationRequests(): HasMany
    {
        return $this->hasMany(AdditionalInformationRequest::class);
    }
}
