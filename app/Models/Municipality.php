<?php

namespace App\Models;

use Database\Factories\MunicipalityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $tax_number
 * @property string $contact_email
 * @property array<string, mixed>|null $settings
 * @property bool $active
 * @property string|null $official_logo_path
 */
class Municipality extends Model
{
    /** @use HasFactory<MunicipalityFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'tax_number',
        'contact_email',
        'settings',
        'active',
        'official_logo_path',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'active' => 'boolean',
        ];
    }

    /** @return HasOne<MunicipalityOnboardingRun, $this> */
    public function onboardingRun(): HasOne
    {
        return $this->hasOne(MunicipalityOnboardingRun::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Program, $this>
     */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /**
     * @return HasMany<MunicipalityFeatureEntitlement, $this>
     */
    public function featureEntitlements(): HasMany
    {
        return $this->hasMany(MunicipalityFeatureEntitlement::class);
    }
}
