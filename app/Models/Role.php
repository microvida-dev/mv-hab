<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    protected $fillable = [
        'municipality_id',
        'template_key',
        'template_version',
        'template_fingerprint',
        'name',
        'label',
        'description',
        'scope',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @param Builder<Role> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isSystem(): bool
    {
        return $this->is_system;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isMunicipalCustom(): bool
    {
        return ! $this->is_system && $this->scope === 'municipal';
    }

    public function isTemplateBased(): bool
    {
        return $this->isMunicipalCustom()
            && $this->template_key !== null
            && $this->template_version !== null
            && $this->template_fingerprint !== null;
    }

    /** @return BelongsTo<Municipality, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()
            ->where(function ($query) use ($permission) {
                $query->where('name', $permission)
                    ->orWhere('name', '*');
            })
            ->exists();
    }
}
