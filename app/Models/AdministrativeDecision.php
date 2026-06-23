<?php

namespace App\Models;

use App\Enums\AdministrativeDecisionResult;
use App\Enums\AdministrativeDecisionStatus;
use App\Enums\AdministrativeDecisionType;
use Database\Factories\AdministrativeDecisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdministrativeDecision extends Model
{
    /** @use HasFactory<AdministrativeDecisionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'summary',
        'legal_basis',
        'grounds',
        'candidate_visible',
    ];

    protected function casts(): array
    {
        return [
            'decision_type' => AdministrativeDecisionType::class,
            'decision_result' => AdministrativeDecisionResult::class,
            'status' => AdministrativeDecisionStatus::class,
            'decided_at' => 'datetime',
            'approved_at' => 'datetime',
            'candidate_visible' => 'boolean',
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
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
