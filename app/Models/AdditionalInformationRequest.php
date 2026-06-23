<?php

namespace App\Models;

use App\Enums\AdditionalInformationRequestStatus;
use Database\Factories\AdditionalInformationRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdditionalInformationRequest extends Model
{
    /** @use HasFactory<AdditionalInformationRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['subject', 'message', 'instructions', 'deadline_at', 'internal_notes'];

    protected function casts(): array
    {
        return [
            'status' => AdditionalInformationRequestStatus::class,
            'deadline_at' => 'datetime',
            'issued_at' => 'datetime',
            'responded_at' => 'datetime',
            'closed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'request_number';
    }

    /**
     * @return BelongsTo<Complaint, $this>
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * @return HasMany<AdditionalInformationResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(AdditionalInformationResponse::class);
    }
}
