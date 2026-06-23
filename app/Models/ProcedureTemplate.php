<?php

namespace App\Models;

use App\Enums\ProcedureTemplateStatus;
use App\Enums\ProcedureTemplateType;
use Database\Factories\ProcedureTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ProcedureTemplateType $type
 * @property ProcedureTemplateStatus $status
 */
class ProcedureTemplate extends Model
{
    /** @use HasFactory<ProcedureTemplateFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'description',
        'content',
        'variables',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProcedureTemplateType::class,
            'status' => ProcedureTemplateStatus::class,
            'variables' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'template_number';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
