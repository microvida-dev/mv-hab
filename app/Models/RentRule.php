<?php

namespace App\Models;

use Database\Factories\RentRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentRule extends Model
{
    /** @use HasFactory<RentRuleFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'minimum_value' => 'decimal:2',
            'maximum_value' => 'decimal:2',
            'fixed_amount' => 'decimal:2',
            'percentage' => 'decimal:2',
            'minimum_result' => 'decimal:2',
            'maximum_result' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<RentRuleSet, $this>
     */
    public function rentRuleSet(): BelongsTo
    {
        return $this->belongsTo(RentRuleSet::class);
    }
}
