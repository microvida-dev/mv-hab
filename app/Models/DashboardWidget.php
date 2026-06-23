<?php

namespace App\Models;

use App\Enums\DashboardWidgetType;
use Database\Factories\DashboardWidgetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidget extends Model
{
    /** @use HasFactory<DashboardWidgetFactory> */
    use HasFactory;

    protected $guarded = ['id', 'required_permission'];

    protected function casts(): array
    {
        return [
            'widget_type' => DashboardWidgetType::class,
            'configuration' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DashboardDefinition, $this>
     */
    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(DashboardDefinition::class, 'dashboard_definition_id');
    }

    /**
     * @return BelongsTo<IndicatorDefinition, $this>
     */
    public function indicator(): BelongsTo
    {
        return $this->belongsTo(IndicatorDefinition::class, 'indicator_definition_id');
    }
}
