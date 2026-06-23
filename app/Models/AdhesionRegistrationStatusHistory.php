<?php

namespace App\Models;

use App\Enums\AdhesionRegistrationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdhesionRegistrationStatusHistory extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    protected $fillable = [
        'adhesion_registration_id',
        'from_status',
        'to_status',
        'changed_by',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => AdhesionRegistrationStatus::class,
            'to_status' => AdhesionRegistrationStatus::class,
        ];
    }

    /**
     * @return BelongsTo<AdhesionRegistration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(AdhesionRegistration::class, 'adhesion_registration_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
