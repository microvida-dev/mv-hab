<?php

namespace App\Models;

use App\Enums\ApplicationSnapshotType;
use Database\Factories\ApplicationSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationSnapshot extends Model
{
    /** @use HasFactory<ApplicationSnapshotFactory> */
    use HasFactory;

    protected $fillable = [
        'snapshot_type',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_type' => ApplicationSnapshotType::class,
            'data' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
