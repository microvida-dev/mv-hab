<?php

namespace App\Models;

use Database\Factories\BackofficeDashboardSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackofficeDashboardSnapshot extends Model
{
    /** @use HasFactory<BackofficeDashboardSnapshotFactory> */
    use HasFactory;

    protected $fillable = [
        'municipality_id',
        'program_id',
        'contest_id',
        'period_start',
        'period_end',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
            'generated_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'snapshot_number';
    }

    /**
     * @return BelongsTo<Municipality, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
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
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
