<?php

namespace App\Models;

use App\Enums\ExportScope;
use App\Enums\ReportAccessType;
use App\Enums\ReportFormat;
use Database\Factories\ReportAccessLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAccessLog extends Model
{
    /** @use HasFactory<ReportAccessLogFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'access_type' => ReportAccessType::class,
            'format' => ReportFormat::class,
            'scope' => ExportScope::class,
            'filters' => 'array',
            'accessed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ReportDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class, 'report_definition_id');
    }

    /**
     * @return BelongsTo<DashboardDefinition, $this>
     */
    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(DashboardDefinition::class, 'dashboard_definition_id');
    }
}
