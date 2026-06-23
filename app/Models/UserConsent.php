<?php

namespace App\Models;

use App\Enums\ConsentStatus;
use Database\Factories\UserConsentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConsent extends Model
{
    /** @use HasFactory<UserConsentFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ConsentStatus::class,
            'consented_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'expires_at' => 'datetime',
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
     * @return BelongsTo<ConsentPurpose, $this>
     */
    public function purpose(): BelongsTo
    {
        return $this->belongsTo(ConsentPurpose::class, 'consent_purpose_id');
    }
}
