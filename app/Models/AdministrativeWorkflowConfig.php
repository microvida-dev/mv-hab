<?php

namespace App\Models;

use Database\Factories\AdministrativeWorkflowConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdministrativeWorkflowConfig extends Model
{
    /** @use HasFactory<AdministrativeWorkflowConfigFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'program_id',
        'contest_id',
        'name',
        'is_active',
        'default_correction_deadline_days',
        'allow_deadline_extension',
        'max_deadline_extensions',
        'auto_mark_overdue',
        'requires_decision_approval',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allow_deadline_extension' => 'boolean',
            'auto_mark_overdue' => 'boolean',
            'requires_decision_approval' => 'boolean',
        ];
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
}
