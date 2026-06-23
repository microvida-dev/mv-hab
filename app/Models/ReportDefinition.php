<?php

namespace App\Models;

use App\Enums\ReportSensitivityLevel;
use App\Enums\ReportType;
use Database\Factories\ReportDefinitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ReportType $report_type
 * @property ReportSensitivityLevel|null $sensitivity_level
 */
class ReportDefinition extends Model
{
    /** @use HasFactory<ReportDefinitionFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'sensitivity_level', 'required_permission', 'query_service', 'query_method', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'report_type' => ReportType::class,
            'sensitivity_level' => ReportSensitivityLevel::class,
            'available_formats' => 'array',
            'available_scopes' => 'array',
            'filter_schema' => 'array',
            'requires_filters' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ReportRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(ReportRun::class);
    }

    /**
     * @return HasMany<ReportFilterPreset, $this>
     */
    public function presets(): HasMany
    {
        return $this->hasMany(ReportFilterPreset::class);
    }
}
