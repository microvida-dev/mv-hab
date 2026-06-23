<?php

namespace App\Models;

use App\Enums\ApplicationReportStatus;
use App\Enums\ReportFormat;
use Database\Factories\ApplicationReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ReportFormat $format
 * @property array<string, mixed>|null $payload
 */
class ApplicationReport extends Model
{
    /** @use HasFactory<ApplicationReportFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'format',
        'title',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationReportStatus::class,
            'format' => ReportFormat::class,
            'payload' => 'array',
            'generated_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'report_number';
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<Contest, $this>
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
