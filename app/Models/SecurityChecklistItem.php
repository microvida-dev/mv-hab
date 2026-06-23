<?php

namespace App\Models;

use App\Enums\SecurityChecklistStatus;
use Database\Factories\SecurityChecklistItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecurityChecklistItem extends Model
{
    /** @use HasFactory<SecurityChecklistItemFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => SecurityChecklistStatus::class,
            'checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SecurityChecklist, $this>
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(SecurityChecklist::class, 'security_checklist_id');
    }
}
