<?php

namespace App\Models;

use Database\Factories\MfaDeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MfaDevice extends Model
{
    /** @use HasFactory<MfaDeviceFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'secret_encrypted' => 'encrypted',
            'confirmed_at' => 'datetime',
            'last_used_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null && $this->disabled_at === null;
    }
}
