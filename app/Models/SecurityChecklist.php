<?php

namespace App\Models;

use App\Enums\SecurityChecklistStatus;
use Database\Factories\SecurityChecklistFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
