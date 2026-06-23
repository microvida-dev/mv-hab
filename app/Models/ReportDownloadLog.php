<?php

namespace App\Models;

use Database\Factories\ReportDownloadLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportDownloadLog extends Model
{
    /** @use HasFactory<ReportDownloadLogFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['downloaded_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<ReportExport, $this>
     */
    public function export(): BelongsTo
    {
        return $this->belongsTo(ReportExport::class, 'report_export_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
