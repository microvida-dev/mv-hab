<?php

namespace App\Models;

use App\Enums\RegistrationRenewalStatus;
use Database\Factories\RegistrationRenewalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegistrationRenewal extends Model
{
    /** @use HasFactory<RegistrationRenewalFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'adhesion_registration_id',
        'renewal_number',
        'status',
        'reason',
        'previous_snapshot',
        'updated_snapshot',
        'changed_fields',
        'missing_fields',
        'started_at',
        'submitted_at',
        'completed_at',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RegistrationRenewalStatus::class,
            'previous_snapshot' => 'array',
            'updated_snapshot' => 'array',
            'changed_fields' => 'array',
            'missing_fields' => 'array',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<AdhesionRegistration, $this>
     */
    public function adhesionRegistration(): BelongsTo
    {
        return $this->belongsTo(AdhesionRegistration::class);
    }
}
