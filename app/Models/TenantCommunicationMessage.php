<?php

namespace App\Models;

use Database\Factories\TenantCommunicationMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantCommunicationMessage extends Model
{
    /** @use HasFactory<TenantCommunicationMessageFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'read_at', 'created_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'visible_to_tenant' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TenantCommunication, $this>
     */
    public function communication(): BelongsTo
    {
        return $this->belongsTo(TenantCommunication::class, 'tenant_communication_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
