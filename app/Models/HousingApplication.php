<?php

namespace App\Models;

use App\Enums\HousingApplicationStatus;
use Database\Factories\HousingApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HousingApplication extends Model
{
    /** @use HasFactory<HousingApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'citizen_id',
        'household_id',
        'status',
        'priority_score',
        'notes',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'status' => HousingApplicationStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Citizen, $this>
     */
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    /**
     * @return BelongsTo<Household, $this>
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
