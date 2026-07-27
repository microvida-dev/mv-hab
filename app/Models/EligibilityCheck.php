<?php

namespace App\Models;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\EligibilityCheckStatus;
use App\Enums\EligibilityCheckType;
use App\Enums\EligibilityResult;
use Database\Factories\EligibilityCheckFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $program_id
 * @property int|null $contest_id
 * @property int|null $application_id
 * @property int|null $regulatory_snapshot_id
 * @property AffordableRentLegalRegime|null $legal_regime
 * @property EligibilityResult|null $result
 */
class EligibilityCheck extends Model
{
    /** @use HasFactory<EligibilityCheckFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'result', 'status', 'summary', 'missing_data', 'warnings', 'executed_at'];

    protected function casts(): array
    {
        return [
            'check_type' => EligibilityCheckType::class,
            'legal_regime' => AffordableRentLegalRegime::class,
            'status' => EligibilityCheckStatus::class,
            'result' => EligibilityResult::class,
            'missing_data' => 'array',
            'warnings' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EligibilityRuleSet, $this>
     */
    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(EligibilityRuleSet::class, 'eligibility_rule_set_id');
    }

    /** @return BelongsTo<RegulatorySnapshot, $this> */
    public function regulatorySnapshot(): BelongsTo
    {
        return $this->belongsTo(RegulatorySnapshot::class);
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
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<AdhesionRegistration, $this>
     */
    public function adhesionRegistration(): BelongsTo
    {
        return $this->belongsTo(AdhesionRegistration::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    /**
     * @return HasMany<EligibilityCheckResult, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(EligibilityCheckResult::class);
    }

    /**
     * @return HasMany<EligibilitySnapshot, $this>
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(EligibilitySnapshot::class);
    }
}
