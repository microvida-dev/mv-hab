<?php

namespace App\Models;

use App\Enums\ConsentLegalBasis;
use Database\Factories\ConsentPurposeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsentPurpose extends Model
{
    /** @use HasFactory<ConsentPurposeFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'legal_basis' => ConsentLegalBasis::class,
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'requires_explicit_consent' => 'boolean',
        ];
    }

    /**
     * @return HasMany<UserConsent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
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
}
