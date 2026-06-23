<?php

namespace App\Models;

use App\Enums\ProcedureMinuteStatus;
use Database\Factories\ProcedureMinuteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcedureMinute extends Model
{
    /** @use HasFactory<ProcedureMinuteFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'meeting_date',
        'subject',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProcedureMinuteStatus::class,
            'meeting_date' => 'date',
            'payload' => 'array',
            'generated_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'minute_number';
    }

    /**
     * @return BelongsTo<Contest, $this>
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<ProcedureTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ProcedureTemplate::class, 'procedure_template_id');
    }
}
