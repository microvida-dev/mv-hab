<?php

namespace App\Models;

use App\Enums\DashboardType;
use Database\Factories\DashboardDefinitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property DashboardType $dashboard_type
 */
class DashboardDefinition extends Model
{
    /** @use HasFactory<DashboardDefinitionFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'required_permission', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'dashboard_type' => DashboardType::class,
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'default_filters' => 'array',
        ];
    }

    /**
     * @return HasMany<DashboardWidget, $this>
     */
    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class)->orderBy('sort_order');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
