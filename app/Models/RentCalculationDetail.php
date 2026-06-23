<?php

namespace App\Models;

use App\Enums\RentCalculationResult;
use Database\Factories\RentCalculationDetailFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentCalculationDetail extends Model
{
    /** @use HasFactory<RentCalculationDetailFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'result' => RentCalculationResult::class,
            'input_value' => 'decimal:2',
            'output_value' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<RentCalculation, $this>
     */
    public function rentCalculation(): BelongsTo
    {
        return $this->belongsTo(RentCalculation::class);
    }

    /**
     * @return BelongsTo<RentRule, $this>
     */
    public function rentRule(): BelongsTo
    {
        return $this->belongsTo(RentRule::class);
    }
}
