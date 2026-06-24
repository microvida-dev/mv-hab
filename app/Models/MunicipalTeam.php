<?php

namespace App\Models;

use Database\Factories\MunicipalTeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MunicipalTeam extends Model
{
    /** @use HasFactory<MunicipalTeamFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'functional_scopes',
        'manager_user_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'functional_scopes' => 'array',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role_in_team', 'joined_at', 'left_at', 'created_by'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<AccessChangeEvent, $this>
     */
    public function accessChangeEvents(): HasMany
    {
        return $this->hasMany(AccessChangeEvent::class);
    }
}
