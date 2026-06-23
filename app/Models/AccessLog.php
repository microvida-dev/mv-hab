<?php

namespace App\Models;

use App\Enums\AccessLogType;
use Database\Factories\AccessLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property AccessLogType $access_type
 * @property User|null $user
 */
class AccessLog extends Model
{
    /** @use HasFactory<AccessLogFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'access_type' => AccessLogType::class,
            'accessed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function resource(): MorphTo
    {
        return $this->morphTo();
    }
}
