<?php

namespace App\Models;

use App\Enums\AllocationReportStatus;
use Database\Factories\AllocationReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AllocationReport extends Model
{
    /** @use HasFactory<AllocationReportFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'report_number', 'status', 'generated_by', 'generated_at', 'approved_by', 'approved_at', 'published_at', 'file_path', 'file_disk', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => AllocationReportStatus::class,
            'results_summary' => 'array',
            'exceptions_summary' => 'array',
            'refusals_summary' => 'array',
            'reserve_summary' => 'array',
            'generated_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AllocationRun, $this>
     */
    public function allocationRun(): BelongsTo
    {
        return $this->belongsTo(AllocationRun::class);
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<Contest, $this>
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /**
     * @return BelongsTo<DefinitiveList, $this>
     */
    public function definitiveList(): BelongsTo
    {
        return $this->belongsTo(DefinitiveList::class);
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
