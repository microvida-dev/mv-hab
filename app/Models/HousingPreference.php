<?php

namespace App\Models;

use App\Enums\HousingCompatibilityStatus;
use Database\Factories\HousingPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $application_id
 * @property int $user_id
 * @property int $contest_id
 * @property int $contest_housing_unit_id
 * @property int $housing_unit_id
 * @property int $preference_order
 * @property HousingCompatibilityStatus|null $compatibility_status
 * @property array<string, mixed>|null $compatibility_snapshot
 * @property int|null $regulatory_snapshot_id
 * @property Carbon|null $evaluated_at
 * @property Carbon|null $invalidated_at
 * @property Carbon|null $submitted_at
 * @property Carbon|null $locked_at
 */
class HousingPreference extends Model
{
    /** @use HasFactory<HousingPreferenceFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'id',
        'application_id',
        'user_id',
        'contest_id',
        'contest_housing_unit_id',
        'housing_unit_id',
        'regulatory_snapshot_id',
        'legacy_application_preference_id',
        'submitted_at',
        'locked_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'compatibility_status' => HousingCompatibilityStatus::class,
            'compatibility_snapshot' => 'array',
            'evaluated_at' => 'datetime',
            'invalidated_at' => 'datetime',
            'submitted_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
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
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Contest, $this>
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /**
     * @return BelongsTo<ContestHousingUnit, $this>
     */
    public function contestHousingUnit(): BelongsTo
    {
        return $this->belongsTo(ContestHousingUnit::class);
    }

    /**
     * @return BelongsTo<HousingUnit, $this>
     */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /** @return BelongsTo<RegulatorySnapshot, $this> */
    public function regulatorySnapshot(): BelongsTo
    {
        return $this->belongsTo(RegulatorySnapshot::class);
    }

    /** @return BelongsTo<ApplicationPreference, $this> */
    public function legacyApplicationPreference(): BelongsTo
    {
        return $this->belongsTo(
            ApplicationPreference::class,
            'legacy_application_preference_id',
        );
    }
}
