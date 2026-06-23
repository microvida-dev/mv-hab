<?php

namespace App\Models;

use App\Enums\DataSubjectRequestStatus;
use App\Enums\DataSubjectRequestType;
use Database\Factories\DataSubjectRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataSubjectRequest extends Model
{
    /** @use HasFactory<DataSubjectRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'request_type' => DataSubjectRequestType::class,
            'status' => DataSubjectRequestStatus::class,
            'identity_verified_at' => 'datetime',
            'received_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'rejected_at' => 'datetime',
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
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<DataSubjectRequestAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(DataSubjectRequestAction::class);
    }

    /**
     * @return HasMany<DataExportPackage, $this>
     */
    public function exports(): HasMany
    {
        return $this->hasMany(DataExportPackage::class);
    }

    /**
     * @return HasMany<AnonymizationRequest, $this>
     */
    public function anonymizationRequests(): HasMany
    {
        return $this->hasMany(AnonymizationRequest::class);
    }
}
