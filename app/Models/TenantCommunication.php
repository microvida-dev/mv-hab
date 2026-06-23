<?php

namespace App\Models;

use App\Enums\TenantCommunicationStatus;
use App\Enums\TenantCommunicationVisibility;
use Database\Factories\TenantCommunicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantCommunication extends Model
{
    /** @use HasFactory<TenantCommunicationFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'opened_at', 'last_message_at', 'closed_at', 'archived_at', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => TenantCommunicationStatus::class,
            'visibility' => TenantCommunicationVisibility::class,
            'opened_at' => 'datetime',
            'last_message_at' => 'datetime',
            'closed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Contract, $this> */
    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'lease_contract_id');
    }

    /** @return BelongsTo<HousingUnit, $this> */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /** @return HasMany<TenantCommunicationMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(TenantCommunicationMessage::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
