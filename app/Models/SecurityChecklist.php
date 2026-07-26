<?php

namespace App\Models;

use App\Enums\SecurityChecklistStatus;
use Database\Factories\SecurityChecklistFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int|null $municipality_id
 */
class SecurityChecklist extends Model
{
    /** @use HasFactory<SecurityChecklistFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => SecurityChecklistStatus::class,
            'started_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<SecurityChecklistItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SecurityChecklistItem::class);
    }

    /** @return BelongsTo<Municipality, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }
}
