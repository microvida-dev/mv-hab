<?php

namespace App\Models;

use Database\Factories\IndicatorSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorSnapshot extends Model
{
    /** @use HasFactory<IndicatorSnapshotFactory> */
    use HasFactory;

    protected $guarded = ['id', 'indicator_definition_id', 'calculated_by'];

    protected function casts(): array
    {
        return [
            'value_numeric' => 'decimal:4',
            'value_json' => 'array',
            'filters' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<IndicatorDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(IndicatorDefinition::class, 'indicator_definition_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }
}
