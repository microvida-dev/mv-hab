<?php

namespace App\Models;

use App\Enums\HearingStatus;
use App\Enums\HearingType;
use Database\Factories\HearingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property bool $candidate_visible
 * @property HearingStatus $status
 * @property Carbon|null $deadline_at
 */
class Hearing extends Model
{
    /** @use HasFactory<HearingFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['hearing_type', 'subject', 'message', 'legal_basis', 'grounds', 'deadline_at', 'candidate_visible', 'internal_notes'];

    protected function casts(): array
    {
        return [
            'status' => HearingStatus::class,
            'hearing_type' => HearingType::class,
            'deadline_at' => 'datetime',
            'issued_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'closed_at' => 'datetime',
            'candidate_visible' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'hearing_number';
    }

    /**
     * @return BelongsTo<ProvisionalList, $this>
     */
    public function provisionalList(): BelongsTo
    {
        return $this->belongsTo(ProvisionalList::class);
    }

    /**
     * @return BelongsTo<DefinitiveList, $this>
     */
    public function definitiveList(): BelongsTo
    {
        return $this->belongsTo(DefinitiveList::class);
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
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<HearingSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(HearingSubmission::class);
    }
}
