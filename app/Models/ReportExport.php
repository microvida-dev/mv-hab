<?php

namespace App\Models;

use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationResultExportStage;
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
 * @property ApplicationResultExportMode|null $export_mode
 * @property ApplicationResultExportStage|null $processing_stage
 * @property string|null $export_profile
 * @property array<string, mixed>|null $source_metadata
 * @property list<string>|null $formats
 * @property list<string>|null $datasets
 * @property bool $sensitive_fields_included
 * @property bool $document_files_requested
 * @property bool $document_files_included
 * @property Carbon|null $started_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $downloaded_at
 * @property Carbon|null $snapshot_at
 * @property-read ReportRun $run
 * @property-read Municipality|null $municipality
 * @property-read Contest|null $contest
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
        'municipality_id',
        'contest_id',
        'export_profile',
        'export_mode',
        'snapshot_at',
        'source_metadata',
        'source_fingerprint',
        'manifest_sha256',
        'package_sha256',
        'processing_stage',
        'progress',
        'started_at',
        'failed_at',
        'failure_code',
        'idempotency_key',
        'formats',
        'datasets',
        'sensitive_fields_included',
        'document_files_requested',
        'document_files_included',
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
            'export_mode' => ApplicationResultExportMode::class,
            'processing_stage' => ApplicationResultExportStage::class,
            'source_metadata' => 'array',
            'formats' => 'array',
            'datasets' => 'array',
            'progress' => 'integer',
            'sensitive_fields_included' => 'boolean',
            'document_files_requested' => 'boolean',
            'document_files_included' => 'boolean',
            'snapshot_at' => 'datetime',
            'started_at' => 'datetime',
            'failed_at' => 'datetime',
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

    /** @return BelongsTo<Municipality, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /** @return BelongsTo<Contest, $this> */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    public function isTemporalApplicationResultExport(): bool
    {
        return $this->export_profile === 'temporal_application_results';
    }

    /**
     * @return HasMany<ReportDownloadLog, $this>
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(ReportDownloadLog::class);
    }
}
