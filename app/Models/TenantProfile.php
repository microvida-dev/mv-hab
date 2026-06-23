<?php

namespace App\Models;

use App\Enums\TenantPortalStatus;
use Database\Factories\TenantProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property TenantPortalStatus $status
 * @property Carbon|null $activated_at
 */
class TenantProfile extends Model
{
    /** @use HasFactory<TenantProfileFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'activated_at', 'blocked_at', 'archived_at', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => TenantPortalStatus::class,
            'activated_at' => 'datetime',
            'blocked_at' => 'datetime',
            'archived_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return HasMany<TenantContractAccess, $this>
     */
    public function contractAccesses(): HasMany
    {
        return $this->hasMany(TenantContractAccess::class);
    }
}
