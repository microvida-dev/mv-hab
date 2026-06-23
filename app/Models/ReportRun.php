<?php

namespace App\Models;

use App\Enums\ExportScope;
use App\Enums\ReportFormat;
use App\Enums\ReportRunStatus;
use Database\Factories\ReportRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property ExportScope $scope
 * @property array<string, mixed>|null $filters
 * @property-read ReportDefinition $definition
 */
class ReportRun extends Model
{
    /** @use HasFactory<ReportRunFactory> */
    use HasFactory;

    protected $guarded = ['id', 'public_id', 'user_id', 'status', 'row_count', 'started_at', 'completed_at', 'failed_at', 'error_message'];

    protected function casts(): array
    {
        return [
            'status' => ReportRunStatus::class,
            'format' => ReportFormat::class,
            'scope' => ExportScope::class,
            'filters' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<ReportDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class, 'report_definition_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ReportExport, $this>
     */
    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class);
    }
}
