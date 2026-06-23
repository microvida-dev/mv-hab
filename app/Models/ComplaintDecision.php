<?php

namespace App\Models;

use App\Enums\ComplaintDecisionResult;
use App\Enums\ComplaintDecisionStatus;
use Database\Factories\ComplaintDecisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplaintDecision extends Model
{
    /** @use HasFactory<ComplaintDecisionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['decision_result', 'summary', 'grounds', 'legal_basis', 'effects_on_ranking', 'effects_on_exclusion', 'requires_list_update', 'candidate_visible'];

    protected function casts(): array
    {
        return [
            'status' => ComplaintDecisionStatus::class,
            'decision_result' => ComplaintDecisionResult::class,
            'requires_list_update' => 'boolean',
            'candidate_visible' => 'boolean',
            'proposed_at' => 'datetime',
            'approved_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'decision_number';
    }

    /**
     * @return BelongsTo<Complaint, $this>
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<ProvisionalList, $this>
     */
    public function provisionalList(): BelongsTo
    {
        return $this->belongsTo(ProvisionalList::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
