<?php

namespace App\Models;

use App\Enums\ExportScope;
use App\Enums\ReportExportStatus;
use App\Enums\ReportFormat;
use Database\Factories\ReportExportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property ReportExportStatus $status
 * @property ReportFormat $requested_format
 * @property ReportFormat $format
 * @property ExportScope $scope
 * @property Carbon|null $expires_at
 * @property-read ReportRun $run
 */
class ReportExport extends Model
{
    /** @use HasFactory<ReportExportFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $guarded = [
        'id',
        'public_id',
        'report_run_id',
        'user_id',
        'status',
        'disk',
        'file_path',
        'file_name',
        'file_size',
        'completed_at',
        'expires_at',
        'downloaded_at',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReportExportStatus::class,
            'requested_format' => ReportFormat::class,
            'format' => ReportFormat::class,
            'scope' => ExportScope::class,
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
            'downloaded_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<ReportRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(ReportRun::class, 'report_run_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ReportDownloadLog, $this>
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(ReportDownloadLog::class);
    }
}
