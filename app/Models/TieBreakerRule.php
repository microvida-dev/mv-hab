<?php

namespace App\Models;

use App\Enums\TieBreakerDirection;
use Database\Factories\TieBreakerRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $code
 * @property string $name
 * @property string $target
 * @property TieBreakerDirection $direction
 * @property bool $is_active
 */
class TieBreakerRule extends Model
{
    /** @use HasFactory<TieBreakerRuleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'target',
        'direction',
        'priority_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'direction' => TieBreakerDirection::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ScoringRuleSet, $this>
     */
    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(ScoringRuleSet::class, 'scoring_rule_set_id');
    }
}
