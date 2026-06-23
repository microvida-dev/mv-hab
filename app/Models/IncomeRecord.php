<?php

namespace App\Models;

use Database\Factories\IncomeRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomeRecord extends Model
{
    /** @use HasFactory<IncomeRecordFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'income_source_id',
        'description',
        'monthly_amount',
        'annual_amount',
        'reference_year',
        'starts_at',
        'ends_at',
        'is_current',
        'is_taxable',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'monthly_amount' => 'decimal:2',
            'annual_amount' => 'decimal:2',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_current' => 'boolean',
            'is_taxable' => 'boolean',
        ];
    }

    /** @return BelongsTo<HouseholdMember, $this> */
    public function householdMember(): BelongsTo
    {
        return $this->belongsTo(HouseholdMember::class);
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<AdhesionRegistration, $this> */
    public function adhesionRegistration(): BelongsTo
    {
        return $this->belongsTo(AdhesionRegistration::class);
    }

    /** @return BelongsTo<IncomeSource, $this> */
    public function incomeSource(): BelongsTo
    {
        return $this->belongsTo(IncomeSource::class);
    }

    /** @return HasMany<DocumentSubmission, $this> */
    public function documentSubmissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }
}
