<?php

namespace App\Models;

use App\Enums\SecurityAlertSeverity;
use Database\Factories\SecurityAlertRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property SecurityAlertSeverity $severity
 * @property string $name
 * @property string|null $description
 */
class SecurityAlertRule extends Model
{
    /** @use HasFactory<SecurityAlertRuleFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'severity' => SecurityAlertSeverity::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<SecurityAlert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(SecurityAlert::class);
    }
}
