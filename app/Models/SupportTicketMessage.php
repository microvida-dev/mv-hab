<?php

namespace App\Models;

use App\Enums\MessageVisibility;
use Database\Factories\SupportTicketMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicketMessage extends Model
{
    /** @use HasFactory<SupportTicketMessageFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'support_ticket_id', 'sender_user_id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => MessageVisibility::class,
            'metadata' => 'array',
            'read_by_candidate_at' => 'datetime',
            'read_by_staff_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SupportTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    /**
     * @return HasMany<SupportTicketAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCandidateVisible(Builder $query): Builder
    {
        return $query->whereIn('visibility', [
            MessageVisibility::CandidateVisible->value,
            MessageVisibility::System->value,
        ]);
    }

    public function isCandidateVisible(): bool
    {
        return in_array(
            MessageVisibility::tryFrom((string) $this->getRawOriginal('visibility')),
            [MessageVisibility::CandidateVisible, MessageVisibility::System],
            true,
        );
    }
}
