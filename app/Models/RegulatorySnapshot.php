<?php

namespace App\Models;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\RegulatoryContext;
use Carbon\CarbonImmutable;
use Database\Factories\RegulatorySnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * @property int $id
 * @property int|null $municipality_id
 * @property AffordableRentLegalRegime $legal_regime
 * @property RegulatoryContext $context
 * @property CarbonImmutable $reference_date
 * @property CarbonImmutable $locked_at
 * @property array<string, int> $rule_sets
 * @property array<string, mixed> $limits
 * @property array<string, mixed> $parameters
 * @property array<string, mixed> $municipal_overlay
 */
class RegulatorySnapshot extends Model
{
    /** @use HasFactory<RegulatorySnapshotFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'legal_regime' => AffordableRentLegalRegime::class,
            'context' => RegulatoryContext::class,
            'reference_date' => 'immutable_datetime',
            'rule_sets' => 'array',
            'limits' => 'array',
            'parameters' => 'array',
            'municipal_overlay' => 'array',
            'locked_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Um snapshot regulamentar bloqueado é imutável.');
        });

        static::deleting(function (): void {
            throw new LogicException('Um snapshot regulamentar bloqueado não pode ser eliminado.');
        });
    }

    /** @return BelongsTo<Municipality, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /** @return BelongsTo<AffordableRentRegulatoryProfile, $this> */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(AffordableRentRegulatoryProfile::class, 'regulatory_profile_id');
    }

    /** @return MorphTo<Model, $this> */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
