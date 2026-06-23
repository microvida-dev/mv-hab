<?php

namespace App\Models;

use App\Enums\IndicatorCategory;
use App\Enums\IndicatorValueType;
use Database\Factories\IndicatorDefinitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IndicatorDefinition extends Model
{
    /** @use HasFactory<IndicatorDefinitionFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'calculation_service', 'calculation_method', 'required_permission', 'is_sensitive'];

    protected function casts(): array
    {
        return [
            'category' => IndicatorCategory::class,
            'value_type' => IndicatorValueType::class,
            'is_sensitive' => 'boolean',
            'is_active' => 'boolean',
            'default_filters' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * @return HasMany<IndicatorSnapshot, $this>
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(IndicatorSnapshot::class);
    }

    /**
     * @return HasMany<DashboardWidget, $this>
     */
    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class);
    }
}
