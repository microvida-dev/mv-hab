<?php

namespace App\Models;

use App\Enums\RentLimitConfigurationStatus;
use Database\Factories\RentLimitTableManifestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $regulatory_profile_id
 * @property int $rent_rule_set_id
 * @property string $source_document
 * @property string $source_reference
 * @property string $source_version
 * @property Carbon $effective_from
 * @property Carbon|null $effective_until
 * @property string|null $checksum
 * @property int $row_count
 * @property list<string> $municipality_coverage
 * @property list<string> $typology_coverage
 * @property RentLimitConfigurationStatus $validation_status
 * @property bool $demo_only
 * @property Carbon|null $validated_at
 * @property int|null $validated_by
 */
class RentLimitTableManifest extends Model
{
    /** @use HasFactory<RentLimitTableManifestFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
            'municipality_coverage' => 'array',
            'typology_coverage' => 'array',
            'validation_status' => RentLimitConfigurationStatus::class,
            'demo_only' => 'boolean',
            'validated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AffordableRentRegulatoryProfile, $this> */
    public function regulatoryProfile(): BelongsTo
    {
        return $this->belongsTo(AffordableRentRegulatoryProfile::class);
    }

    /** @return BelongsTo<RentRuleSet, $this> */
    public function rentRuleSet(): BelongsTo
    {
        return $this->belongsTo(RentRuleSet::class);
    }

    /** @return BelongsTo<User, $this> */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /** @return HasMany<RentLimitTableRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(RentLimitTableRow::class, 'manifest_id')
            ->orderBy('municipality_code')
            ->orderBy('typology');
    }
}
