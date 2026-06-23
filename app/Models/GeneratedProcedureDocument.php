<?php

namespace App\Models;

use App\Enums\GeneratedProcedureDocumentStatus;
use App\Enums\ProcedureTemplateType;
use App\Enums\ReportFormat;
use Database\Factories\GeneratedProcedureDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneratedProcedureDocument extends Model
{
    /** @use HasFactory<GeneratedProcedureDocumentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'format',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProcedureTemplateType::class,
            'status' => GeneratedProcedureDocumentStatus::class,
            'format' => ReportFormat::class,
            'payload' => 'array',
            'generated_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'document_number';
    }

    /**
     * @return BelongsTo<ProcedureTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ProcedureTemplate::class, 'procedure_template_id');
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
     * @return MorphTo<Model, $this>
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
